<?php

namespace App\Jobs;

use App\Enums\StatusCekTautan;
use App\Events\TautanBerkasTidakDapatDiakses;
use App\Models\TautanBerkas;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

/**
 * Memeriksa keteraksesan tautan dari sisi anonim (tanpa cookie/login) tanpa mengunduh isi berkas.
 * Hasil informatif saja: tidak pernah memblokir penyimpanan.
 */
class PeriksaTautanBerkas implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public bool $deleteWhenMissingModels = true;

    public int $uniqueFor = 600;

    public int $tries = 2;

    public function __construct(public TautanBerkas $tautan)
    {
        $this->onQueue(config('puspresma.antrean.berkas'));
    }

    public function uniqueId(): string
    {
        return (string) $this->tautan->getKey();
    }

    public function handle(): void
    {
        $tautan = $this->tautan->fresh();
        if ($tautan === null) {
            return;
        }

        $sebelumnya = $tautan->status_cek;
        $baru = $this->dapatDiakses($tautan->url) ? StatusCekTautan::DapatDiakses : StatusCekTautan::TidakDapatDiakses;

        $tautan->forceFill(['status_cek' => $baru, 'dicek_pada' => now()])->saveQuietly();

        if ($sebelumnya === StatusCekTautan::DapatDiakses && $baru === StatusCekTautan::TidakDapatDiakses) {
            TautanBerkasTidakDapatDiakses::dispatch($tautan);
        }
    }

    private function dapatDiakses(string $url): bool
    {
        try {
            $respons = $this->klien()->head($url);

            if (! $this->sukses($respons)) {
                // Beberapa layanan menolak HEAD; ulangi sebagai GET 1 byte tanpa membaca isi.
                $respons = $this->klien()->withHeaders(['Range' => 'bytes=0-0'])->withOptions(['stream' => true])->get($url);
            }

            return $this->sukses($respons);
        } catch (ConnectionException) {
            return false;
        }
    }

    private function klien(): PendingRequest
    {
        return Http::timeout((int) config('berkas.timeout_cek'))
            ->withOptions(['allow_redirects' => ['max' => (int) config('berkas.maks_redirect')], 'cookies' => false])
            ->withUserAgent('Puspresma-PeriksaTautan/1.0');
    }

    /** 200/206 dan bukan berakhir di halaman login Google. */
    private function sukses(Response $respons): bool
    {
        if (! in_array($respons->status(), [200, 206], true)) {
            return false;
        }

        return $respons->effectiveUri()?->getHost() !== 'accounts.google.com';
    }
}

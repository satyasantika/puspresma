<?php

namespace App\Jobs;

use App\Events\KandidatDuplikatDitemukan;
use App\Models\KandidatDuplikat;
use App\Models\Prestasi;
use App\Services\DeteksiDuplikatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Mencari dan menyimpan kandidat duplikat saat prestasi diajukan; idempoten (upsert) dan unik per prestasi. */
class DeteksiDuplikatPrestasi implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public bool $deleteWhenMissingModels = true;

    public int $uniqueFor = 60;

    public function __construct(public Prestasi $prestasi) {}

    public function uniqueId(): string
    {
        return (string) $this->prestasi->getKey();
    }

    public function handle(DeteksiDuplikatService $layanan): void
    {
        $baru = 0;

        foreach ($layanan->cari($this->prestasi) as $kandidat) {
            // status_tinjau yang sudah ditinjau tidak ditimpa: hanya skor yang diperbarui.
            $rekaman = KandidatDuplikat::firstOrNew([
                'prestasi_id' => $this->prestasi->getKey(),
                'pembanding_id' => $kandidat['pembanding_id'],
                'alasan' => $kandidat['alasan'],
            ]);
            $rekaman->exists || $baru++;
            $rekaman->skor = $kandidat['skor'];
            $rekaman->save();
        }

        if ($baru > 0) {
            KandidatDuplikatDitemukan::dispatch($this->prestasi, $baru);
        }
    }
}

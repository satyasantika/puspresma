<?php

namespace App\Jobs;

use App\Enums\StatusBerkasEkspor;
use App\Models\BerkasEkspor;
use App\Notifications\BerkasEksporSiap;
use App\Services\Ekspor\PenulisBerkas;
use App\Services\Ekspor\RegistriLaporan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/** Menulis ekspor besar ke disk tmp (streaming 500 baris), kedaluwarsa 24 jam, lalu memberi tahu pemohon. */
class EksporBerkasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public BerkasEkspor $berkas)
    {
        $this->onConnection(config('puspresma.koneksi_berat'))->onQueue(config('puspresma.antrean.ekspor'));
    }

    public function handle(): void
    {
        $berkas = $this->berkas;
        $berkas->update(['status' => StatusBerkasEkspor::Proses]);

        try {
            $pengguna = $berkas->user;
            $laporan = RegistriLaporan::buat($berkas->jenis);
            $filter = $berkas->parameter ?? [];
            $disk = Storage::disk('tmp');
            $path = "ekspor/{$berkas->user_id}/".Str::uuid7().".{$berkas->format}";
            $disk->makeDirectory("ekspor/{$berkas->user_id}");

            if ($berkas->format === 'pdf') {
                $disk->put($path, PenulisBerkas::pdf($laporan, $pengguna, $filter, $laporan->lembar($filter, $pengguna)));
                $jumlah = $berkas->jumlah_baris;
            } else {
                $jumlah = PenulisBerkas::buku($disk->path($path), $berkas->format, $laporan->lembar($filter, $pengguna));
            }

            $berkas->update(['status' => StatusBerkasEkspor::Siap, 'path' => $path, 'jumlah_baris' => $jumlah, 'kedaluwarsa_at' => now()->addHours(24)]);
            $pengguna->notify(new BerkasEksporSiap($laporan->judul(), route('ekspor.unduh', $berkas)));
        } catch (Throwable $e) {
            report($e);
            $berkas->update(['status' => StatusBerkasEkspor::Gagal, 'pesan_galat' => Str::limit($e->getMessage(), 500)]);
        }
    }
}

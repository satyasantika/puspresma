<?php

namespace App\Jobs;

use App\Actions\Poin\HitungPoinPrestasi;
use App\Enums\StatusPrestasi;
use App\Models\Prestasi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HitungPoinPrestasiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public Prestasi $prestasi) {}

    public function handle(HitungPoinPrestasi $aksi): void
    {
        $terkini = $this->prestasi->fresh();

        // Status dibaca ulang: verifikasi bisa dibuka kembali sebelum job berjalan.
        if ($terkini !== null && $terkini->status === StatusPrestasi::Terverifikasi) {
            $aksi->jalankan($terkini);
        }
    }
}

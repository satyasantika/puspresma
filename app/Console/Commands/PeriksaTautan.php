<?php

namespace App\Console\Commands;

use App\Jobs\PeriksaTautanBerkas;
use App\Models\TautanBerkas;
use Illuminate\Console\Command;

class PeriksaTautan extends Command
{
    protected $signature = 'puspresma:periksa-tautan';

    protected $description = 'Jadwalkan pemeriksaan ulang semua tautan berkas aktif (BR-30), terverifikasi dan foto galeri lebih dulu';

    public function handle(): int
    {
        $urutan = 0;

        // Prioritas: tautan prestasi terverifikasi dan foto penyerahan; jeda 1 detik antar-permintaan agar tidak dibatasi Google.
        TautanBerkas::query()
            ->selectRaw('tautan_berkas.*, (jenis = ? OR EXISTS (SELECT 1 FROM prestasi WHERE prestasi.id = tautan_berkas.pemilik_id AND pemilik_type = ? AND prestasi.status = ?)) AS prioritas', ['foto_penyerahan', 'prestasi', 'terverifikasi'])
            ->orderByDesc('prioritas')->orderBy('id')
            ->chunk(200, function ($kelompok) use (&$urutan): void {
                foreach ($kelompok as $tautan) {
                    PeriksaTautanBerkas::dispatch($tautan)->delay(now()->addSeconds($urutan++));
                }
            });

        $this->info("{$urutan} pemeriksaan dijadwalkan.");

        return self::SUCCESS;
    }
}

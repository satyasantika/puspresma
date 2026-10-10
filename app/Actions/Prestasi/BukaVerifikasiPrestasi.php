<?php

namespace App\Actions\Prestasi;

use App\Enums\StatusPrestasi;
use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** terverifikasi → perlu_perbaikan oleh admin-fakultas; poin anggota dikosongkan, snapshot tautan lama tetap (BR-12, BR-28). */
class BukaVerifikasiPrestasi
{
    public function __construct(private readonly TransisiStatus $transisi) {}

    public function jalankan(Prestasi $prestasi, User $pelaku, string $catatan): Prestasi
    {
        Gate::forUser($pelaku)->authorize('bukaVerifikasi', $prestasi);

        $hasil = DB::transaction(function () use ($prestasi, $pelaku, $catatan): Prestasi {
            $hasil = $this->transisi->ubah($prestasi, StatusPrestasi::PerluPerbaikan, $pelaku, $catatan, [
                'diverifikasi_at' => null,
                'diverifikasi_oleh' => null,
                'verifikasi_tanpa_konfirmasi' => false,
                'catatan_verifikasi' => trim($catatan),
            ]);

            PrestasiAnggota::where('prestasi_id', $prestasi->id)->get()->each->update(['poin' => null]);

            return $hasil;
        });

        DB::afterCommit(function (): void {
            Cache::increment('statistik:versi');
            Cache::increment('galeri:versi');
        });

        return $hasil;
    }
}

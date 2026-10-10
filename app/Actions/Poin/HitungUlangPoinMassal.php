<?php

namespace App\Actions\Poin;

use App\Models\Prestasi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

/** Hitung ulang poin seluruh prestasi terverifikasi sesuai bobot saat ini; tercatat di audit (BR-18). */
class HitungUlangPoinMassal
{
    public function __construct(private readonly HitungPoinPrestasi $hitung) {}

    /** @return int jumlah prestasi yang dihitung ulang */
    public function jalankan(User $pelaku, ?int $tahun = null): int
    {
        if (! $pelaku->can('poin.hitung-ulang')) {
            throw new AuthorizationException('Anda tidak berhak menghitung ulang poin.');
        }

        $jumlah = 0;
        Prestasi::query()->terverifikasi()
            ->when($tahun !== null, fn ($q) => $q->where('tahun', $tahun))
            ->chunkById(200, function ($kelompok) use (&$jumlah): void {
                foreach ($kelompok as $prestasi) {
                    $this->hitung->jalankan($prestasi) && $jumlah++;
                }
            });

        activity('poin')->causedBy($pelaku)->event('hitung_ulang')
            ->withProperties(['tahun' => $tahun, 'jumlah_prestasi' => $jumlah])
            ->log('Poin dihitung ulang');

        return $jumlah;
    }
}

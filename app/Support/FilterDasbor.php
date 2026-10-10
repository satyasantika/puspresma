<?php

namespace App\Support;

use App\Models\User;

/** Filter dasbor: admin-prodi dikunci ke prodinya; peran lain bebas memilih (pimpinan melihat semua prodi). */
class FilterDasbor
{
    public static function prodiEfektif(User $pengguna, ?string $dipilih): ?string
    {
        if ($pengguna->can('prestasi.lihat-semua')) {
            return filled($dipilih) ? $dipilih : null;
        }

        return $pengguna->prodi_id;
    }

    public static function tahun(mixed $nilai): ?int
    {
        return filled($nilai) ? (int) $nilai : null;
    }
}

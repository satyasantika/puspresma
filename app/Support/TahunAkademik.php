<?php

namespace App\Support;

use Carbon\CarbonInterface;

/** Tahun akademik dari tanggal dan bulan awal tahun akademik (BR-21), mis. "2025/2026". */
class TahunAkademik
{
    public static function dari(CarbonInterface $tanggal, int $awalBulan = 8): string
    {
        $awal = $tanggal->month >= $awalBulan ? $tanggal->year : $tanggal->year - 1;

        return $awal.'/'.($awal + 1);
    }
}

<?php

namespace App\Services\Ekspor;

use InvalidArgumentException;

/** Pemetaan kode laporan → kelas (dipakai job antrean untuk membentuk ulang laporan dari berkas_ekspor.jenis). */
class RegistriLaporan
{
    /** @var array<string, class-string<LaporanEkspor>> */
    public const DAFTAR = [
        'rekap_prestasi' => RekapPrestasiLaporan::class,
        'rekap_anggota' => RekapAnggotaLaporan::class,
        'peringkat_poin' => PeringkatPoinLaporan::class,
        'katalog' => RekapKatalogLaporan::class,
    ];

    public static function buat(string $jenis): LaporanEkspor
    {
        $kelas = self::DAFTAR[$jenis] ?? throw new InvalidArgumentException("Jenis laporan '{$jenis}' tidak dikenal.");

        return app($kelas);
    }
}

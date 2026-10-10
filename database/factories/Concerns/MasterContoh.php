<?php

namespace Database\Factories\Concerns;

use App\Models\Capaian;
use App\Models\JenisKegiatan;
use App\Models\Tingkat;

/** Baris master minimal untuk factory/test (firstOrCreate; selaras dengan seeder). */
class MasterContoh
{
    public static function jenis(string $kode = 'kompetisi'): JenisKegiatan
    {
        return JenisKegiatan::firstOrCreate(['kode' => $kode], ['nama' => ucfirst($kode), 'urutan' => $kode === 'kompetisi' ? 1 : 2]);
    }

    public static function tingkat(string $kode = 'nasional'): Tingkat
    {
        $urutan = ['lokal' => 1, 'wilayah' => 2, 'nasional' => 3, 'internasional' => 4][$kode] ?? 1;

        return Tingkat::firstOrCreate(['kode' => $kode], ['nama' => ucfirst($kode), 'urutan' => $urutan]);
    }

    public static function capaian(string $kode = 'juara-1', string $jenis = 'kompetisi'): Capaian
    {
        $jenisKegiatan = self::jenis($jenis);

        return Capaian::firstOrCreate(
            ['jenis_kegiatan_id' => $jenisKegiatan->id, 'kode' => $kode],
            ['nama' => ucfirst(str_replace('-', ' ', $kode)), 'is_juara' => str_starts_with($kode, 'juara')],
        );
    }
}

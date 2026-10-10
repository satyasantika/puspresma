<?php

namespace Database\Seeders;

use App\Models\JenisKegiatan;
use Illuminate\Database\Seeder;

class JenisKegiatanSeeder extends Seeder
{
    // Selaraskan dengan pedoman SIMKATMAWA terbaru (perlu verifikasi).
    // Kategori lain (mis. sertifikasi, hasil karya, kewirausahaan) ditambahkan lewat panel bila diperlukan.
    public function run(): void
    {
        $data = [
            ['kode' => 'kompetisi', 'nama' => 'Kompetisi/Lomba', 'urutan' => 1, 'wajib_penyelenggara' => true],
            ['kode' => 'rekognisi', 'nama' => 'Rekognisi', 'urutan' => 2, 'wajib_penyelenggara' => true],
        ];

        foreach ($data as $baris) {
            JenisKegiatan::withTrashed()->updateOrCreate(['kode' => $baris['kode']], $baris);
        }
    }
}

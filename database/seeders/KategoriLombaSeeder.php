<?php

namespace Database\Seeders;

use App\Models\JenisKegiatan;
use App\Models\KategoriLomba;
use Illuminate\Database\Seeder;

class KategoriLombaSeeder extends Seeder
{
    // Selaraskan dengan pedoman SIMKATMAWA terbaru (perlu verifikasi).
    public function run(): void
    {
        $kompetisi = JenisKegiatan::where('kode', 'kompetisi')->value('id');
        $rekognisi = JenisKegiatan::where('kode', 'rekognisi')->value('id');

        $daftar = [
            'Sains & Matematika', 'Teknologi & Inovasi', 'Karya Tulis Ilmiah', 'Pendidikan & Pembelajaran',
            'Bahasa & Sastra', 'Debat', 'Seni & Budaya', 'Olahraga', 'Kewirausahaan', 'Keagamaan', 'Lainnya',
        ];

        foreach ($daftar as $nama) {
            KategoriLomba::withTrashed()->updateOrCreate(['jenis_kegiatan_id' => $kompetisi, 'nama' => $nama]);
        }

        KategoriLomba::withTrashed()->updateOrCreate(['jenis_kegiatan_id' => $rekognisi, 'nama' => 'Lainnya']);
    }
}

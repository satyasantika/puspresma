<?php

namespace Database\Seeders;

use App\Models\Tingkat;
use Illuminate\Database\Seeder;

class TingkatSeeder extends Seeder
{
    // Selaraskan dengan pedoman SIMKATMAWA terbaru (perlu verifikasi).
    public function run(): void
    {
        $data = [
            ['kode' => 'lokal', 'nama' => 'Lokal/Perguruan Tinggi', 'urutan' => 1],
            ['kode' => 'wilayah', 'nama' => 'Wilayah/Provinsi', 'urutan' => 2],
            ['kode' => 'nasional', 'nama' => 'Nasional', 'urutan' => 3],
            ['kode' => 'internasional', 'nama' => 'Internasional', 'urutan' => 4],
        ];

        foreach ($data as $baris) {
            Tingkat::withTrashed()->updateOrCreate(['kode' => $baris['kode']], $baris);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Capaian;
use App\Models\JenisKegiatan;
use Illuminate\Database\Seeder;

class CapaianSeeder extends Seeder
{
    // Selaraskan dengan pedoman SIMKATMAWA terbaru (perlu verifikasi); daftar rekognisi seluruhnya perlu verifikasi.
    public function run(): void
    {
        $daftar = [
            'kompetisi' => [
                ['juara-1', 'Juara 1', true], ['juara-2', 'Juara 2', true], ['juara-3', 'Juara 3', true],
                ['harapan-1', 'Harapan 1', false], ['harapan-2', 'Harapan 2', false], ['harapan-3', 'Harapan 3', false],
                ['penghargaan-khusus', 'Penghargaan Khusus', false], ['finalis', 'Finalis', false], ['peserta', 'Peserta', false],
            ],
            'rekognisi' => [
                ['narasumber', 'Narasumber', false], ['juri', 'Juri', false], ['karya-diakui', 'Karya Diakui', false],
                ['penghargaan-lembaga', 'Penghargaan Lembaga', false], ['lainnya', 'Lainnya', false],
            ],
        ];

        foreach ($daftar as $kodeJenis => $baris) {
            $jenisId = JenisKegiatan::where('kode', $kodeJenis)->value('id');

            foreach ($baris as $i => [$kode, $nama, $juara]) {
                Capaian::withTrashed()->updateOrCreate(
                    ['jenis_kegiatan_id' => $jenisId, 'kode' => $kode],
                    ['nama' => $nama, 'is_juara' => $juara, 'urutan' => $i + 1],
                );
            }
        }
    }
}

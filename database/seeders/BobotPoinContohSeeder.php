<?php

namespace Database\Seeders;

use App\Models\BobotPoin;
use App\Models\Capaian;
use App\Models\JenisKegiatan;
use App\Models\Tingkat;
use Illuminate\Database\Seeder;

/**
 * Contoh, bukan acuan resmi. TIDAK didaftarkan di DatabaseSeeder; jalankan manual:
 * php artisan db:seed --class=BobotPoinContohSeeder. Bobot final ditetapkan melalui SK Dekan.
 */
class BobotPoinContohSeeder extends Seeder
{
    /** Poin juara-1 per tingkat; capaian lain = persentase dari juara-1. */
    private const JUARA_1 = ['lokal' => 10, 'wilayah' => 20, 'nasional' => 40, 'internasional' => 60];

    private const PERSEN = [
        'juara-2' => 80, 'juara-3' => 60, 'harapan-1' => 40, 'harapan-2' => 40, 'harapan-3' => 40,
        'finalis' => 25, 'peserta' => 0,
    ];

    public function run(): void
    {
        $kompetisi = JenisKegiatan::where('kode', 'kompetisi')->firstOrFail();
        $rekognisi = JenisKegiatan::where('kode', 'rekognisi')->firstOrFail();
        $tingkat = Tingkat::pluck('id', 'kode');
        $mulai = '2026-01-01';

        foreach (self::JUARA_1 as $kodeTingkat => $poin) {
            $this->isi($kompetisi, 'juara-1', $tingkat[$kodeTingkat], $poin, $mulai);
            foreach (self::PERSEN as $kodeCapaian => $persen) {
                $this->isi($kompetisi, $kodeCapaian, $tingkat[$kodeTingkat], $poin * $persen / 100, $mulai);
            }
        }

        foreach (['narasumber', 'juri'] as $kodeCapaian) {
            $this->isi($rekognisi, $kodeCapaian, $tingkat['nasional'], 20, $mulai);
        }
    }

    private function isi(JenisKegiatan $jenis, string $kodeCapaian, string $tingkatId, int|float $poin, string $mulai): void
    {
        $capaian = Capaian::where('jenis_kegiatan_id', $jenis->id)->where('kode', $kodeCapaian)->first();
        if ($capaian === null) {
            return;
        }

        BobotPoin::updateOrCreate(
            ['jenis_kegiatan_id' => $jenis->id, 'tingkat_id' => $tingkatId, 'capaian_id' => $capaian->id, 'berlaku_mulai' => $mulai],
            ['poin' => number_format($poin, 2, '.', ''), 'dasar_penetapan' => 'Contoh, bukan acuan resmi'],
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\Prodi;
use Illuminate\Database\Seeder;

class ProdiSeeder extends Seeder
{
    /** Upsert berdasarkan kode dari database/seeders/data/prodi.csv. */
    public function run(?string $berkas = null): void
    {
        $berkas ??= database_path('seeders/data/prodi.csv');
        $handle = fopen($berkas, 'r');

        if ($handle === false) {
            return;
        }

        $header = fgetcsv($handle, escape: '');

        while (($baris = fgetcsv($handle, escape: '')) !== false) {
            if ($header === false || count($baris) !== count($header)) {
                continue;
            }

            $data = array_combine($header, array_map('trim', $baris));

            Prodi::withTrashed()->updateOrCreate(
                ['kode' => $data['kode']],
                [
                    'kode_pddikti' => $data['kode_pddikti'] ?: null,
                    'nama' => $data['nama'],
                    'jenjang' => $data['jenjang'] ?: 'S1',
                ],
            );
        }

        fclose($handle);
    }
}

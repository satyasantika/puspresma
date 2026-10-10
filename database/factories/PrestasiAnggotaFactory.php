<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PrestasiAnggota> */
class PrestasiAnggotaFactory extends Factory
{
    protected $model = PrestasiAnggota::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'prestasi_id' => Prestasi::factory(),
            'mahasiswa_id' => Mahasiswa::factory(),
            'prodi_id' => fn (array $atribut) => Mahasiswa::find($atribut['mahasiswa_id'])->prodi_id,
            'peran' => 'anggota',
            'is_pengaju' => false,
        ];
    }
}

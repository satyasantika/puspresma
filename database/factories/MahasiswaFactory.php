<?php

namespace Database\Factories;

use App\Enums\StatusMahasiswa;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Mahasiswa> */
class MahasiswaFactory extends Factory
{
    protected $model = Mahasiswa::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $npm = fake()->unique()->numerify('##########');

        return [
            'npm' => $npm,
            'nama' => fake()->name(),
            'prodi_id' => Prodi::factory(),
            'angkatan' => fake()->numberBetween(2019, (int) date('Y')),
            'email' => $npm.'@'.config('puspresma.domain_mahasiswa'),
            'nomor_hp' => fake()->numerify('08##########'),
            'status_mahasiswa' => StatusMahasiswa::Aktif,
        ];
    }
}

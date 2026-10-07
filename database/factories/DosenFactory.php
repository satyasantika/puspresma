<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\Prodi;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Dosen> */
class DosenFactory extends Factory
{
    protected $model = Dosen::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'nidn' => fake()->unique()->numerify('##########'),
            'nip' => fake()->unique()->numerify('##################'),
            'nama' => fake()->name(),
            'gelar_depan' => 'Dr.',
            'gelar_belakang' => 'M.Pd.',
            'prodi_id' => Prodi::factory(),
            'email' => fake()->unique()->userName().'@'.config('puspresma.domain_pegawai'),
        ];
    }
}

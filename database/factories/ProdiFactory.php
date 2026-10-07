<?php

namespace Database\Factories;

use App\Enums\JenjangProdi;
use App\Models\Prodi;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Prodi> */
class ProdiFactory extends Factory
{
    protected $model = Prodi::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'kode' => strtoupper(fake()->unique()->bothify('P??##')),
            'kode_pddikti' => fake()->numerify('#####'),
            'nama' => 'Pendidikan '.fake()->unique()->words(2, true),
            'jenjang' => JenjangProdi::S1,
            'is_active' => true,
        ];
    }
}

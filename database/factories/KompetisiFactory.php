<?php

namespace Database\Factories;

use App\Enums\StatusKompetisi;
use App\Models\Kompetisi;
use App\Models\User;
use Database\Factories\Concerns\MasterContoh;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Kompetisi> */
class KompetisiFactory extends Factory
{
    protected $model = Kompetisi::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'judul' => 'Olimpiade '.fake()->unique()->words(2, true).' '.fake()->year(),
            'penyelenggara' => fake()->company(),
            'jenis_kegiatan_id' => fn () => MasterContoh::jenis()->id,
            'tingkat_id' => fn () => MasterContoh::tingkat('nasional')->id,
            'deskripsi' => '<p>Kompetisi tahunan untuk mahasiswa.</p>',
            'url_pendaftaran' => 'https://pendaftaran.example.org/daftar',
            'tenggat_pendaftaran' => now()->addDays(20),
            'biaya_pendaftaran' => '50000.00',
            'status' => StatusKompetisi::Draf,
            'dibuat_oleh' => User::factory(),
        ];
    }

    public function terbit(): static
    {
        return $this->state(['status' => StatusKompetisi::Terbit, 'diterbitkan_at' => now()]);
    }

    public function diarsipkan(): static
    {
        return $this->state(['status' => StatusKompetisi::Diarsipkan, 'tenggat_pendaftaran' => now()->subDays(5)]);
    }
}

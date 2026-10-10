<?php

namespace Database\Factories;

use App\Enums\JenisTautan;
use App\Enums\StatusCekTautan;
use App\Models\Prestasi;
use App\Models\TautanBerkas;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TautanBerkas> */
class TautanBerkasFactory extends Factory
{
    protected $model = TautanBerkas::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $id = fake()->unique()->bothify('1??????????????????#######');

        return [
            'pemilik_type' => 'prestasi',
            'pemilik_id' => Prestasi::factory(),
            'jenis' => JenisTautan::SertifikatPiagam,
            'label' => 'Sertifikat',
            'url' => "https://drive.google.com/file/d/{$id}/view?usp=sharing",
            'ditambahkan_oleh' => User::factory(),
        ];
    }

    public function untukPrestasi(Prestasi $prestasi): static
    {
        return $this->state(['pemilik_type' => 'prestasi', 'pemilik_id' => $prestasi->id, 'ditambahkan_oleh' => $prestasi->dibuat_oleh]);
    }

    public function sertifikat(): static
    {
        return $this->state(['jenis' => JenisTautan::SertifikatPiagam, 'label' => 'Sertifikat']);
    }

    /** Foto penyerahan yang dibagikan publik dan disetujui tampil di galeri (BR-16). */
    public function fotoPublik(): static
    {
        return $this->state(['jenis' => JenisTautan::FotoPenyerahan, 'label' => 'Foto penyerahan', 'tampil_publik' => true])
            ->afterCreating(fn (TautanBerkas $tautan) => $tautan->forceFill(['status_cek' => StatusCekTautan::DapatDiakses, 'dicek_pada' => now()])->saveQuietly());
    }
}

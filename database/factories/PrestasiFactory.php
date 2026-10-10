<?php

namespace Database\Factories;

use App\Enums\BentukPartisipasi;
use App\Enums\BidangPrestasi;
use App\Enums\StatusPrestasi;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\User;
use Database\Factories\Concerns\MasterContoh;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Prestasi> */
class PrestasiFactory extends Factory
{
    protected $model = Prestasi::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $selesai = fake()->dateTimeBetween('-6 months', '-1 week');

        return [
            'pengaju_id' => Mahasiswa::factory(),
            // Diselaraskan dengan prodi pengaju saat dibuat (lihat configure()).
            'prodi_id' => fn (array $atribut) => Mahasiswa::find($atribut['pengaju_id'])->prodi_id,
            'dibuat_oleh' => User::factory(),
            'jenis_kegiatan_id' => fn () => MasterContoh::jenis()->id,
            'bidang' => BidangPrestasi::Akademik,
            'tingkat_id' => fn () => MasterContoh::tingkat('nasional')->id,
            'capaian_id' => fn () => MasterContoh::capaian('juara-1')->id,
            'bentuk' => BentukPartisipasi::Individu,
            'nama_kegiatan' => 'Lomba Debat Nasional '.fake()->unique()->numerify('###'),
            'penyelenggara' => fake()->company(),
            'tanggal_mulai' => (clone $selesai)->modify('-2 days')->format('Y-m-d'),
            'tanggal_selesai' => $selesai->format('Y-m-d'),
            'moda' => 'luring',
            'lokasi' => fake()->city(),
            'jumlah_peserta' => 100,
            'jumlah_pt_peserta' => 30,
            'status' => StatusPrestasi::Draf,
        ];
    }

    /** Pengaju otomatis menjadi anggota (is_pengaju, ketua) kecuali dimatikan lewat tanpaAnggotaPengaju(). */
    public function configure(): static
    {
        return $this->afterCreating(function (Prestasi $prestasi): void {
            if ($prestasi->getAttribute('__tanpa_pengaju')) {
                return;
            }
            $pengaju = $prestasi->pengaju;
            PrestasiAnggotaFactory::new()->create([
                'prestasi_id' => $prestasi->id,
                'mahasiswa_id' => $pengaju->id,
                'prodi_id' => $pengaju->prodi_id,
                'peran' => 'ketua',
                'is_pengaju' => true,
            ]);
        });
    }

    public function draf(): static
    {
        return $this->state(['status' => StatusPrestasi::Draf]);
    }

    public function diajukan(): static
    {
        return $this->state(fn () => [
            'status' => StatusPrestasi::Diajukan,
            'nomor_registrasi' => sprintf('PRS-%d-%05d', now()->year, fake()->unique()->numberBetween(1, 99999)),
            'diajukan_at' => now(),
        ]);
    }

    public function terverifikasi(): static
    {
        return $this->diajukan()->state(fn () => [
            'status' => StatusPrestasi::Terverifikasi,
            'diverifikasi_at' => now(),
            'diverifikasi_oleh' => User::factory(),
        ]);
    }

    public function kelompok(): static
    {
        return $this->state(['bentuk' => BentukPartisipasi::Kelompok]);
    }

    public function internasional(): static
    {
        return $this->state(fn () => [
            'tingkat_id' => MasterContoh::tingkat('internasional')->id,
            'jumlah_negara_peserta' => 5,
            'negara_peserta' => 'Indonesia, Malaysia, Thailand, Filipina, Vietnam',
        ]);
    }
}

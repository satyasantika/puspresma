<?php

namespace Database\Seeders;

use App\Models\Capaian;
use App\Models\Dosen;
use App\Models\JenisKegiatan;
use App\Models\Kompetisi;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\Tingkat;
use App\Models\User;
use App\Services\NormalisasiTeks;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Data demo untuk lokal/uji kinerja (TIDAK didaftarkan di DatabaseSeeder). Jumlah prestasi dari DEMO_PRESTASI (bawaan 60):
 * php artisan db:seed --class=DemoSeeder. Memakai sisipan massal agar 10.000 prestasi selesai dalam hitungan detik.
 */
class DemoSeeder extends Seeder
{
    private const STATUS = ['terverifikasi' => 60, 'diajukan' => 15, 'draf' => 10, 'perlu_perbaikan' => 10, 'ditolak' => 5];

    public function run(): void
    {
        $this->call([PeranDanIzinSeeder::class, JenisKegiatanSeeder::class, KategoriLombaSeeder::class, TingkatSeeder::class, CapaianSeeder::class, PengaturanSeeder::class]);

        $jumlah = (int) config('puspresma.demo_prestasi');
        $prodi = collect(range(1, 3))->map(fn ($i) => Prodi::firstOrCreate(['kode' => "DEMO-{$i}"], ['nama' => "Pendidikan Demo {$i}", 'kode_pddikti' => "9900{$i}"]));
        $mahasiswa = Mahasiswa::factory()->count(200)->sequence(fn ($s) => ['prodi_id' => $prodi[$s->index % 3]->id])->create();
        Dosen::factory()->count(20)->sequence(fn ($s) => ['prodi_id' => $prodi[$s->index % 3]->id])->create();
        $pembuat = User::factory()->create(['name' => 'Admin Demo', 'email' => 'demo-'.Str::random(6).'@unsil.ac.id']);
        $admin = $pembuat->id;

        $jenis = JenisKegiatan::firstWhere('kode', 'kompetisi');
        $tingkat = Tingkat::pluck('id')->all();
        $capaian = Capaian::where('jenis_kegiatan_id', $jenis->id)->pluck('id')->all();
        $nomorAwal = 1;

        foreach (collect(range(1, $jumlah))->chunk(500) as $kelompok) {
            $prestasi = $anggota = $tautan = $snapshot = [];
            foreach ($kelompok as $i) {
                $m = $mahasiswa->random();
                $status = $this->pilihStatus();
                $selesai = now()->subDays(random_int(1, 1000));
                $id = (string) Str::uuid7();
                $nama = 'Lomba '.fake()->words(3, true)." Nasional {$i}";
                $sekarang = now();

                $prestasi[] = [
                    'id' => $id, 'nomor_registrasi' => $status === 'draf' ? null : sprintf('PRS-%d-%05d', $selesai->year, $nomorAwal++),
                    'pengaju_id' => $m->id, 'prodi_id' => $m->prodi_id, 'dibuat_oleh' => $admin, 'jenis_kegiatan_id' => $jenis->id,
                    'bidang' => random_int(0, 1) ? 'akademik' : 'non_akademik', 'tingkat_id' => $tingkat[array_rand($tingkat)], 'capaian_id' => $capaian[array_rand($capaian)],
                    'bentuk' => 'kelompok', 'nama_kegiatan' => $nama, 'nama_kegiatan_normal' => NormalisasiTeks::kegiatan($nama), 'penyelenggara' => fake()->company(),
                    'tanggal_mulai' => $selesai->copy()->subDays(2)->toDateString(), 'tanggal_selesai' => $selesai->toDateString(), 'tahun' => $selesai->year,
                    'moda' => 'luring', 'lokasi' => fake()->city(), 'negara_penyelenggara' => 'Indonesia', 'status_konfirmasi_pembimbing' => 'tidak_ada', 'status' => $status,
                    'diajukan_at' => $status === 'draf' ? null : $selesai, 'diverifikasi_at' => $status === 'terverifikasi' ? $sekarang : null,
                    'verifikasi_tanpa_konfirmasi' => false, 'disembunyikan_dari_galeri' => false, 'created_at' => $sekarang, 'updated_at' => $sekarang,
                ];

                foreach ($mahasiswa->random(random_int(1, 3))->push($m)->unique('id') as $urut => $a) {
                    $anggota[] = ['id' => (string) Str::uuid7(), 'prestasi_id' => $id, 'mahasiswa_id' => $a->id, 'prodi_id' => $a->prodi_id,
                        'peran' => $a->id === $m->id ? 'ketua' : 'anggota', 'is_pengaju' => $a->id === $m->id, 'setuju_publikasi' => (bool) random_int(0, 1),
                        'poin' => $status === 'terverifikasi' ? '10.00' : null, 'created_at' => $sekarang, 'updated_at' => $sekarang];
                }

                $url = 'https://drive.google.com/file/d/DEMO'.Str::random(24).'/view';
                $idTautan = (string) Str::uuid7();
                $tautan[] = ['id' => $idTautan, 'pemilik_type' => 'prestasi', 'pemilik_id' => $id, 'jenis' => 'sertifikat_piagam', 'label' => 'Sertifikat', 'url' => $url,
                    'penyedia' => 'google_drive', 'drive_file_id' => substr($url, 39, 28), 'status_cek' => 'dapat_diakses', 'ditambahkan_oleh' => $admin,
                    'tampil_publik' => false, 'url_normal' => $url, 'created_at' => $sekarang, 'updated_at' => $sekarang];
                if ($status === 'terverifikasi') {
                    $snapshot[] = ['id' => (string) Str::uuid7(), 'prestasi_id' => $id, 'tautan_berkas_id' => $idTautan, 'jenis' => 'sertifikat_piagam', 'url' => $url,
                        'drive_file_id' => substr($url, 39, 28), 'status_cek' => 'dapat_diakses', 'alasan' => 'verifikasi', 'dibuka_verifikator' => true, 'user_id' => $admin, 'created_at' => $sekarang];
                }
            }

            DB::table('prestasi')->insert($prestasi);
            DB::table('prestasi_anggota')->insert($anggota);
            DB::table('tautan_berkas')->insert($tautan);
            DB::table('snapshot_tautan_verifikasi')->insert($snapshot);
        }

        Kompetisi::factory()->count(10)->terbit()->create(['dibuat_oleh' => $admin]);
    }

    private function pilihStatus(): string
    {
        $angka = random_int(1, 100);
        foreach (self::STATUS as $status => $bobot) {
            if (($angka -= $bobot) <= 0) {
                return $status;
            }
        }

        return 'draf';
    }
}

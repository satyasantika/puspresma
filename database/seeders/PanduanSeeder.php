<?php

namespace Database\Seeders;

use App\Enums\StatusKompetisi;
use App\Models\Dosen;
use App\Models\Kompetisi;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\Prodi;
use App\Models\TautanBerkas;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Akun demo per peran + data rekaan untuk tangkapan layar panduan (F9.6). HANYA APP_ENV=local.
 * Kata sandi dari PANDUAN_PASSWORD; tanpa data pribadi asli (surel @contoh.test, nama rekaan).
 */
class PanduanSeeder extends Seeder
{
    /** @var list<string> */
    public const PERAN = ['mahasiswa', 'dosen', 'admin-prodi', 'admin-fakultas', 'pimpinan', 'super-admin'];

    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('PanduanSeeder hanya boleh dijalankan di APP_ENV=local.');
        }

        $sandi = config('puspresma.panduan_password');
        if (blank($sandi)) {
            throw new RuntimeException('Isi PANDUAN_PASSWORD di .env sebelum menjalankan PanduanSeeder.');
        }

        $this->call([PeranDanIzinSeeder::class, JenisKegiatanSeeder::class, KategoriLombaSeeder::class, TingkatSeeder::class, CapaianSeeder::class, PengaturanSeeder::class]);

        $prodi = Prodi::firstOrCreate(['kode' => 'PANDUAN-1'], ['nama' => 'Pendidikan Contoh', 'kode_pddikti' => '99001']);
        Prodi::firstOrCreate(['kode' => 'PANDUAN-2'], ['nama' => 'Pendidikan Rekaan', 'kode_pddikti' => '99002']);

        $akun = [];
        foreach (self::PERAN as $peran) {
            $akun[$peran] = User::updateOrCreate(
                ['email' => "panduan.{$peran}@contoh.test"],
                ['name' => 'Demo '.ucwords(str_replace('-', ' ', $peran)), 'password' => $sandi, 'is_active' => true, 'prodi_id' => $peran === 'admin-prodi' ? $prodi->id : null],
            );
            $akun[$peran]->forceFill(['email_verified_at' => now()])->save();
            $akun[$peran]->syncRoles([$peran]);
        }

        $dosen = Dosen::factory()->create(['nama' => 'Dr. Contoh Pembimbing', 'prodi_id' => $prodi->id, 'user_id' => $akun['dosen']->id]);
        $saya = Mahasiswa::factory()->create(['nama' => 'Mahasiswa Contoh', 'prodi_id' => $prodi->id, 'user_id' => $akun['mahasiswa']->id]);
        $teman = Mahasiswa::factory()->create(['nama' => 'Rekan Contoh', 'prodi_id' => $prodi->id]);

        $buat = fn (Mahasiswa $m, string $nama) => Prestasi::factory()->state(['nama_kegiatan' => $nama, 'dosen_pembimbing_id' => $dosen->id])
            ->for($m, 'pengaju')->create(['dibuat_oleh' => $akun['mahasiswa']->id]);

        $draf = $buat($saya, 'Lomba Debat Bahasa Indonesia Nasional');
        $diajukan = Prestasi::factory()->diajukan()->for($saya, 'pengaju')->create(['nama_kegiatan' => 'Olimpiade Sains Mahasiswa', 'dibuat_oleh' => $akun['mahasiswa']->id, 'dosen_pembimbing_id' => $dosen->id]);
        $verif = Prestasi::factory()->terverifikasi()->for($saya, 'pengaju')->create(['nama_kegiatan' => 'Kompetisi Karya Tulis Ilmiah', 'dibuat_oleh' => $akun['mahasiswa']->id]);
        $lain = Prestasi::factory()->diajukan()->for($teman, 'pengaju')->create(['nama_kegiatan' => 'Lomba Inovasi Pembelajaran', 'dibuat_oleh' => $akun['mahasiswa']->id]);

        foreach ([$draf, $diajukan, $verif, $lain] as $p) {
            TautanBerkas::factory()->untukPrestasi($p)->sertifikat()->create(['ditambahkan_oleh' => $akun['mahasiswa']->id]);
        }

        Kompetisi::factory()->create(['judul' => 'Olimpiade Pendidikan Nasional', 'status' => StatusKompetisi::Terbit, 'dibuat_oleh' => $akun['admin-fakultas']->id]);
    }
}

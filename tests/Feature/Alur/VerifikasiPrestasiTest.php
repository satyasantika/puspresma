<?php

use App\Actions\Duplikat\TinjauKandidatDuplikat;
use App\Actions\Pembimbing\KonfirmasiPembimbingan;
use App\Actions\Prestasi\AjukanPrestasi;
use App\Actions\Prestasi\BukaVerifikasiPrestasi;
use App\Actions\Prestasi\LoloskanTahapProdi;
use App\Actions\Prestasi\VerifikasiPrestasi;
use App\Enums\StatusKonfirmasiPembimbing;
use App\Enums\StatusPrestasi;
use App\Enums\StatusTinjauDuplikat;
use App\Models\BobotPoin;
use App\Models\Dosen;
use App\Models\KandidatDuplikat;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use App\Models\Prodi;
use App\Models\SnapshotTautanVerifikasi;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Services\Pengaturan;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class]);
    Http::fake(['*' => Http::response('', 200)]);
});

it('menjalankan alur verifikasi lengkap dari pengajuan sampai buka verifikasi dan ajukan ulang', function () {
    Pengaturan::setel('poin_aktif', true);
    Pengaturan::setel('verifikasi_prodi_aktif', true);

    $prodi = Prodi::factory()->create();
    $fakultas = User::factory()->create()->assignRole('admin-fakultas');
    $adminProdi = User::factory()->create(['prodi_id' => $prodi->id])->assignRole('admin-prodi');
    $dosen = Dosen::factory()->create();
    $userDosen = User::factory()->create()->assignRole('dosen');
    $dosen->update(['user_id' => $userDosen->id]);

    // Prestasi lama yang memakai tautan sertifikat yang sama.
    $lama = Prestasi::factory()->diajukan()->create();
    TautanBerkas::factory()->untukPrestasi($lama)->sertifikat()->create(['url' => 'https://drive.google.com/file/d/SAMA777/view']);

    $pengaju = Mahasiswa::factory()->for($prodi)->create();
    $userPengaju = User::factory()->create(['email' => $pengaju->email])->assignRole('mahasiswa');
    $pengaju->update(['user_id' => $userPengaju->id]);
    $prestasi = Prestasi::factory()->for($pengaju, 'pengaju')->create(['dosen_pembimbing_id' => $dosen->id]);
    $tautan = TautanBerkas::factory()->untukPrestasi($prestasi)->sertifikat()->create(['url' => 'https://drive.google.com/file/d/SAMA777/preview']);
    BobotPoin::create([
        'jenis_kegiatan_id' => $prestasi->jenis_kegiatan_id, 'tingkat_id' => $prestasi->tingkat_id, 'capaian_id' => $prestasi->capaian_id,
        'poin' => '40.00', 'berlaku_mulai' => '2020-01-01', 'dasar_penetapan' => 'SK uji',
    ]);

    // 1. diajukan → kandidat duplikat (tautan sama) + konfirmasi pembimbing menunggu
    app(AjukanPrestasi::class)->jalankan($prestasi, $userPengaju);
    $prestasi->refresh();
    expect($prestasi->status)->toBe(StatusPrestasi::Diajukan)
        ->and($prestasi->status_konfirmasi_pembimbing)->toBe(StatusKonfirmasiPembimbing::Menunggu)
        ->and($prestasi->adaKandidatDuplikatTerbuka())->toBeTrue();

    // 2. admin prodi meloloskan tahap 1; verifikasi diblokir selama kandidat belum ditinjau
    app(LoloskanTahapProdi::class)->jalankan($prestasi, $adminProdi);
    expect(fn () => app(VerifikasiPrestasi::class)->jalankan($prestasi->refresh(), $fakultas))->toThrow(ValidationException::class, 'duplikat');

    // 3. ditinjau bukan duplikat, pembimbing mengonfirmasi
    app(TinjauKandidatDuplikat::class)->jalankan(KandidatDuplikat::firstOrFail(), $fakultas, StatusTinjauDuplikat::BukanDuplikat);
    app(KonfirmasiPembimbingan::class)->jalankan($prestasi->refresh(), $userDosen);

    // 4. verifikator membuka tautan lalu memverifikasi
    $this->actingAs($fakultas)->get(route('tautan.buka', $tautan))->assertRedirect($tautan->url);
    app(VerifikasiPrestasi::class)->jalankan($prestasi->refresh(), $fakultas);

    $prestasi->refresh();
    expect($prestasi->status)->toBe(StatusPrestasi::Terverifikasi)
        ->and(SnapshotTautanVerifikasi::where('prestasi_id', $prestasi->id)->count())->toBe(1)
        ->and(SnapshotTautanVerifikasi::first()->dibuka_verifikator)->toBeTrue()
        ->and((string) PrestasiAnggota::where('prestasi_id', $prestasi->id)->value('poin'))->toBe('40.00');

    // 5. buka verifikasi → perlu perbaikan, poin kosong, lalu ajukan ulang dengan nomor tetap
    $nomor = $prestasi->nomor_registrasi;
    app(BukaVerifikasiPrestasi::class)->jalankan($prestasi, $fakultas, 'Tingkat kegiatan perlu dikoreksi.');
    $prestasi->refresh();
    expect($prestasi->status)->toBe(StatusPrestasi::PerluPerbaikan)
        ->and(PrestasiAnggota::where('prestasi_id', $prestasi->id)->whereNotNull('poin')->count())->toBe(0)
        ->and(SnapshotTautanVerifikasi::count())->toBe(1);

    app(AjukanPrestasi::class)->jalankan($prestasi, $userPengaju);
    expect($prestasi->refresh()->status)->toBe(StatusPrestasi::Diajukan)->and($prestasi->nomor_registrasi)->toBe($nomor);
    expect($prestasi->riwayatStatus()->count())->toBeGreaterThanOrEqual(5);
});

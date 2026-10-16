<?php

use App\Filament\Admin\Pages\EksporSimkatmawa;
use App\Filament\Admin\Pages\ImporMahasiswa;
use App\Filament\Admin\Pages\Laporan;
use App\Filament\Admin\Pages\MatriksBobotPoin;
use App\Filament\Admin\Pages\ModerasiGaleri;
use App\Filament\Admin\Pages\PengaturanSistem;
use App\Filament\Admin\Resources\ActivityLogResource;
use App\Filament\Admin\Resources\BobotPoinResource;
use App\Filament\Admin\Resources\KompetisiResource;
use App\Filament\Admin\Resources\PrestasiResource;
use App\Filament\Admin\Resources\SuratKeteranganPrestasiResource;
use App\Filament\Admin\Resources\UserResource;
use App\Filament\Portal\Resources\PrestasiSayaResource;
use App\Models\BerkasEkspor;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\Prodi;
use App\Models\TautanBerkas;
use App\Models\User;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/*
| Matriks otorisasi (PRD §3.2): peran × halaman/aksi penting. Nilai: 200 diizinkan, 403 ditolak,
| 302 dialihkan (peran tanpa akses ke panel admin dialihkan ke portal). UUID milik pihak lain dipakai untuk uji IDOR.
*/

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class]);
    Http::fake(['*' => Http::response('', 200)]);
    Filament::setCurrentPanel('admin');

    $this->prodiA = Prodi::factory()->create();
    $this->prodiB = Prodi::factory()->create();
    $pengguna = fn (string $peran, ?Prodi $prodi = null) => tap(User::factory()->create(['prodi_id' => $prodi?->id])->assignRole($peran), fn ($u) => $u->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save());
    $this->pelaku = [
        'super-admin' => $pengguna('super-admin'),
        'admin-fakultas' => $pengguna('admin-fakultas'),
        'admin-prodi' => $pengguna('admin-prodi', $this->prodiA),
        'pimpinan' => $pengguna('pimpinan'),
        'dosen' => $pengguna('dosen'),
        'mahasiswa' => $pengguna('mahasiswa'),
    ];
    Dosen::factory()->create(['user_id' => $this->pelaku['dosen']->id]);
    Mahasiswa::factory()->for($this->prodiA)->create(['user_id' => $this->pelaku['mahasiswa']->id]);

    // Data milik pihak lain (prodi B): prestasi terverifikasi, tautan buktinya, dan berkas ekspor milik admin-fakultas.
    $this->prestasiLain = Prestasi::factory()->terverifikasi()->for(Mahasiswa::factory()->for($this->prodiB), 'pengaju')->create();
    $this->prestasiDiajukan = Prestasi::factory()->diajukan()->for(Mahasiswa::factory()->for($this->prodiB), 'pengaju')->create();
    $this->tautanLain = TautanBerkas::factory()->untukPrestasi($this->prestasiLain)->sertifikat()->create();
    $this->berkas = BerkasEkspor::create(['jenis' => 'rekap_prestasi', 'format' => 'xlsx', 'status' => 'siap', 'path' => 'ekspor/x/uji.xlsx', 'kedaluwarsa_at' => now()->addHour(), 'user_id' => $this->pelaku['admin-fakultas']->id]);
    Storage::fake('tmp');
    Storage::disk('tmp')->put('ekspor/x/uji.xlsx', 'isi');
});

/** @return array<string, array{0: string, 1: string, 2: int}> */
function kasusMatriks(): array
{
    // kasus => [peran => status]
    $matriks = [
        'daftar pengguna' => ['super-admin' => 200, 'admin-fakultas' => 200, 'admin-prodi' => 403, 'pimpinan' => 403, 'dosen' => 302, 'mahasiswa' => 302],
        'daftar prestasi' => ['super-admin' => 200, 'admin-fakultas' => 200, 'admin-prodi' => 200, 'pimpinan' => 200, 'dosen' => 302, 'mahasiswa' => 302],
        'lihat prestasi prodi lain (IDOR)' => ['super-admin' => 200, 'admin-fakultas' => 200, 'admin-prodi' => 403, 'pimpinan' => 200, 'dosen' => 302, 'mahasiswa' => 302],
        'impor mahasiswa' => ['super-admin' => 200, 'admin-fakultas' => 200, 'admin-prodi' => 403, 'pimpinan' => 403, 'dosen' => 302, 'mahasiswa' => 302],
        'pengaturan sistem' => ['super-admin' => 200, 'admin-fakultas' => 200, 'admin-prodi' => 403, 'pimpinan' => 403, 'dosen' => 302, 'mahasiswa' => 302],
        'bobot poin' => ['super-admin' => 200, 'admin-fakultas' => 200, 'admin-prodi' => 403, 'pimpinan' => 403, 'dosen' => 302, 'mahasiswa' => 302],
        'matriks bobot poin' => ['super-admin' => 200, 'admin-fakultas' => 200, 'admin-prodi' => 403, 'pimpinan' => 403, 'dosen' => 302, 'mahasiswa' => 302],
        'moderasi galeri' => ['super-admin' => 200, 'admin-fakultas' => 200, 'admin-prodi' => 403, 'pimpinan' => 403, 'dosen' => 302, 'mahasiswa' => 302],
        'daftar surat keterangan' => ['super-admin' => 200, 'admin-fakultas' => 200, 'admin-prodi' => 200, 'pimpinan' => 200, 'dosen' => 302, 'mahasiswa' => 302],
        'log aktivitas' => ['super-admin' => 200, 'admin-fakultas' => 200, 'admin-prodi' => 403, 'pimpinan' => 403, 'dosen' => 302, 'mahasiswa' => 302],
        'halaman laporan' => ['super-admin' => 200, 'admin-fakultas' => 200, 'admin-prodi' => 200, 'pimpinan' => 200, 'dosen' => 302, 'mahasiswa' => 302],
        'ekspor simkatmawa' => ['super-admin' => 200, 'admin-fakultas' => 200, 'admin-prodi' => 403, 'pimpinan' => 403, 'dosen' => 302, 'mahasiswa' => 302],
        'buat kompetisi' => ['super-admin' => 200, 'admin-fakultas' => 200, 'admin-prodi' => 200, 'pimpinan' => 403, 'dosen' => 302, 'mahasiswa' => 302],
        'buka tautan bukti prodi lain (IDOR)' => ['super-admin' => 302, 'admin-fakultas' => 302, 'admin-prodi' => 403, 'pimpinan' => 403, 'dosen' => 403, 'mahasiswa' => 403],
        'unduh berkas ekspor milik orang lain (IDOR)' => ['super-admin' => 200, 'admin-fakultas' => 200, 'admin-prodi' => 403, 'pimpinan' => 403, 'dosen' => 403, 'mahasiswa' => 403],
        'buka prestasi orang lain di portal (IDOR)' => ['super-admin' => 302, 'admin-fakultas' => 302, 'admin-prodi' => 302, 'pimpinan' => 302, 'dosen' => 403, 'mahasiswa' => 403],
        'ability verifikasi' => ['super-admin' => 403, 'admin-fakultas' => 200, 'admin-prodi' => 403, 'pimpinan' => 403, 'dosen' => 403, 'mahasiswa' => 403],
        'ability buka verifikasi' => ['super-admin' => 403, 'admin-fakultas' => 200, 'admin-prodi' => 403, 'pimpinan' => 403, 'dosen' => 403, 'mahasiswa' => 403],
    ];

    $kasus = [];
    foreach ($matriks as $nama => $hasil) {
        foreach ($hasil as $peran => $status) {
            $kasus["{$peran} — {$nama}"] = [$peran, $nama, $status];
        }
    }

    return $kasus;
}

it('menegakkan matriks otorisasi PRD §3.2', function (string $peran, string $kasus, int $diharapkan) {
    $pelaku = $this->pelaku[$peran];

    $status = match ($kasus) {
        'daftar pengguna' => $this->actingAs($pelaku)->get(UserResource::getUrl('index'))->getStatusCode(),
        'daftar prestasi' => $this->actingAs($pelaku)->get(PrestasiResource::getUrl('index'))->getStatusCode(),
        'lihat prestasi prodi lain (IDOR)' => $this->actingAs($pelaku)->get(PrestasiResource::getUrl('view', ['record' => $this->prestasiLain]))->getStatusCode(),
        'impor mahasiswa' => $this->actingAs($pelaku)->get(ImporMahasiswa::getUrl())->getStatusCode(),
        'pengaturan sistem' => $this->actingAs($pelaku)->get(PengaturanSistem::getUrl())->getStatusCode(),
        'bobot poin' => $this->actingAs($pelaku)->get(BobotPoinResource::getUrl('index'))->getStatusCode(),
        'matriks bobot poin' => $this->actingAs($pelaku)->get(MatriksBobotPoin::getUrl())->getStatusCode(),
        'moderasi galeri' => $this->actingAs($pelaku)->get(ModerasiGaleri::getUrl())->getStatusCode(),
        'daftar surat keterangan' => $this->actingAs($pelaku)->get(SuratKeteranganPrestasiResource::getUrl('index'))->getStatusCode(),
        'log aktivitas' => $this->actingAs($pelaku)->get(ActivityLogResource::getUrl('index'))->getStatusCode(),
        'halaman laporan' => $this->actingAs($pelaku)->get(Laporan::getUrl())->getStatusCode(),
        'ekspor simkatmawa' => $this->actingAs($pelaku)->get(EksporSimkatmawa::getUrl())->getStatusCode(),
        'buat kompetisi' => $this->actingAs($pelaku)->get(KompetisiResource::getUrl('create'))->getStatusCode(),
        'buka tautan bukti prodi lain (IDOR)' => $this->actingAs($pelaku)->get(route('tautan.buka', $this->tautanLain))->getStatusCode(),
        'unduh berkas ekspor milik orang lain (IDOR)' => $this->actingAs($pelaku)->get(route('ekspor.unduh', $this->berkas))->getStatusCode(),
        'buka prestasi orang lain di portal (IDOR)' => (function () use ($pelaku) {
            Filament::setCurrentPanel('portal');

            return $this->actingAs($pelaku)->get(PrestasiSayaResource::getUrl('view', ['record' => $this->prestasiLain]))->getStatusCode();
        })(),
        'ability verifikasi' => Gate::forUser($pelaku)->allows('verifikasi', $this->prestasiDiajukan) ? 200 : 403,
        'ability buka verifikasi' => Gate::forUser($pelaku)->allows('bukaVerifikasi', $this->prestasiLain) ? 200 : 403,
    };

    expect($status)->toBe($diharapkan);
})->with(kasusMatriks());

it('mencakup minimal 60 kasus matriks otorisasi', function () {
    expect(count(kasusMatriks()))->toBeGreaterThanOrEqual(60);
});

it('mengalihkan tamu ke halaman masuk untuk rute terlindungi dan menolak tanpa membocorkan data', function () {
    $this->get(route('tautan.buka', $this->tautanLain))->assertRedirect(route('login'));
    $this->get(route('ekspor.unduh', $this->berkas))->assertRedirect(route('login'));
    $this->get(PrestasiResource::getUrl('index'))->assertRedirect();
});

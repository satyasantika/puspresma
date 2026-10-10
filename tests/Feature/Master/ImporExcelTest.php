<?php

use App\Enums\StatusImpor;
use App\Filament\Admin\Pages\ImporDosen;
use App\Filament\Admin\Pages\ImporMahasiswa;
use App\Jobs\ImporDosenDariExcel;
use App\Jobs\ImporMahasiswaDariExcel;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\RiwayatImpor;
use App\Models\User;
use App\Notifications\ImporSelesai;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    Filament::setCurrentPanel('admin');
    Storage::fake('tmp');
    $this->prodi = Prodi::factory()->create(['kode' => 'PRD-A']);
    $this->pelaku = User::factory()->create()->assignRole('admin-fakultas');
    $this->pelaku->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();
});

/** Tulis berkas sumber di disk tmp dan buat RiwayatImpor-nya. */
function riwayatDari(string $jenis, array $baris, string $ekstensi = 'xlsx', ?User $pelaku = null): RiwayatImpor
{
    $path = 'impor/'.uniqid().'.'.$ekstensi;
    Storage::disk('tmp')->makeDirectory('impor');
    $penulis = SimpleExcelWriter::create(Storage::disk('tmp')->path($path));
    $penulis->addRows($baris);
    $penulis->close();

    return RiwayatImpor::create([
        'jenis' => $jenis, 'nama_berkas' => basename($path), 'path_tmp' => $path,
        'user_id' => ($pelaku ?? test()->pelaku)->id,
    ]);
}

function barisMahasiswa(array $ubah = []): array
{
    return array_merge([
        'npm' => '2100000001', 'nama' => 'Siti', 'kode_prodi' => 'PRD-A', 'angkatan' => 2021,
        'email' => '2100000001@student.unsil.ac.id', 'status_mahasiswa' => 'aktif',
    ], $ubah);
}

it('mengimpor tiga baris valid dan menulis satu baris gagal ke berkas galat', function () {
    $riwayat = riwayatDari('mahasiswa', [
        barisMahasiswa(),
        barisMahasiswa(['npm' => '2100000002', 'nama' => 'Budi', 'email' => '2100000002@student.unsil.ac.id']),
        barisMahasiswa(['npm' => '2100000003', 'nama' => 'Ani', 'email' => '']),
        barisMahasiswa(['npm' => '2100000004', 'kode_prodi' => 'SALAH', 'email' => '2100000004@student.unsil.ac.id']),
    ]);
    $sumber = $riwayat->path_tmp;

    ImporMahasiswaDariExcel::dispatchSync($riwayat);

    $riwayat->refresh();
    expect($riwayat->status)->toBe(StatusImpor::Selesai)
        ->and($riwayat->total_baris)->toBe(4)
        ->and($riwayat->berhasil)->toBe(3)
        ->and($riwayat->gagal)->toBe(1)
        ->and($riwayat->path_tmp)->toBeNull()
        ->and($riwayat->kedaluwarsa_at->isFuture())->toBeTrue()
        ->and($riwayat->kedaluwarsa_at->lessThanOrEqualTo(now()->addHours(24)->addMinute()))->toBeTrue();
    Storage::disk('tmp')->assertMissing($sumber);
    Storage::disk('tmp')->assertExists($riwayat->path_galat);
    expect(Mahasiswa::count())->toBe(3)
        ->and(Mahasiswa::firstWhere('npm', '2100000001')->prodi_id)->toBe($this->prodi->id);

    $galat = SimpleExcelReader::create(Storage::disk('tmp')->path($riwayat->path_galat))->getRows()->first();
    expect($galat['npm'])->toBe('2100000004')->and($galat['alasan'])->not->toBeEmpty();
});

it('tidak menggandakan data saat impor diulang dan tidak menimpa surel dengan kosong', function () {
    ImporMahasiswaDariExcel::dispatchSync(riwayatDari('mahasiswa', [barisMahasiswa()]));
    ImporMahasiswaDariExcel::dispatchSync(riwayatDari('mahasiswa', [barisMahasiswa(['nama' => 'Siti Baru', 'email' => ''])]));

    $mhs = Mahasiswa::firstWhere('npm', '2100000001');
    expect(Mahasiswa::count())->toBe(1)
        ->and($mhs->nama)->toBe('Siti Baru')
        ->and($mhs->email)->toBe('2100000001@student.unsil.ac.id');
});

it('menolak surel di luar domain mahasiswa, NPM ganda dalam berkas, dan surel milik NPM lain', function () {
    Mahasiswa::factory()->for($this->prodi)->create(['npm' => '2100000009', 'email' => '2100000009@student.unsil.ac.id']);
    $riwayat = riwayatDari('mahasiswa', [
        barisMahasiswa(['email' => 'siti@gmail.com']),
        barisMahasiswa(['npm' => '2100000002', 'email' => '2100000002@student.unsil.ac.id']),
        barisMahasiswa(['npm' => '2100000002', 'email' => '2100000099@student.unsil.ac.id']),
        barisMahasiswa(['npm' => '2100000003', 'email' => '2100000009@student.unsil.ac.id']),
    ]);

    ImporMahasiswaDariExcel::dispatchSync($riwayat);

    expect($riwayat->refresh()->berhasil)->toBe(1)->and($riwayat->gagal)->toBe(3);
});

it('menghapus berkas sumber juga saat job gagal', function () {
    $riwayat = riwayatDari('mahasiswa', [barisMahasiswa()]);
    $sumber = $riwayat->path_tmp;
    Storage::disk('tmp')->put($sumber, 'bukan berkas excel valid');

    ImporMahasiswaDariExcel::dispatchSync($riwayat);

    expect($riwayat->refresh()->status)->toBe(StatusImpor::Gagal)->and($riwayat->path_tmp)->toBeNull();
    Storage::disk('tmp')->assertMissing($sumber);
});

it('mencegah impor paralel lewat lock dan tetap menghapus berkas sumber', function () {
    $lock = Cache::lock('lock:impor:mahasiswa', 900);
    expect($lock->get())->toBeTrue();
    $riwayat = riwayatDari('mahasiswa', [barisMahasiswa()]);
    $sumber = $riwayat->path_tmp;

    ImporMahasiswaDariExcel::dispatchSync($riwayat);

    expect($riwayat->refresh()->status)->toBe(StatusImpor::Gagal)
        ->and($riwayat->pesan)->toBe('Impor lain sedang berjalan.')
        ->and(Mahasiswa::count())->toBe(0);
    Storage::disk('tmp')->assertMissing($sumber);
    $lock->release();
});

it('mengirim notifikasi database ImporSelesai ke pemohon', function () {
    Notification::fake();
    ImporMahasiswaDariExcel::dispatchSync(riwayatDari('mahasiswa', [barisMahasiswa()]));

    Notification::assertSentTo($this->pelaku, ImporSelesai::class);
});

it('mengimpor dosen dengan kunci nidn atau nip tanpa menggandakan', function () {
    $baris = [
        ['nidn' => '0400000001', 'nip' => '', 'nama' => 'Dosen Satu', 'gelar_depan' => 'Dr.', 'gelar_belakang' => 'M.Pd.', 'kode_prodi' => 'PRD-A', 'email' => 'satu@unsil.ac.id'],
        ['nidn' => '', 'nip' => '198001012005011001', 'nama' => 'Dosen Dua', 'gelar_depan' => '', 'gelar_belakang' => '', 'kode_prodi' => '', 'email' => ''],
        ['nidn' => '', 'nip' => '', 'nama' => 'Tanpa Kunci', 'gelar_depan' => '', 'gelar_belakang' => '', 'kode_prodi' => '', 'email' => ''],
        ['nidn' => '0400000003', 'nip' => '', 'nama' => 'Email Asing', 'gelar_depan' => '', 'gelar_belakang' => '', 'kode_prodi' => '', 'email' => 'x@gmail.com'],
    ];

    $riwayat = riwayatDari('dosen', $baris);
    ImporDosenDariExcel::dispatchSync($riwayat);
    ImporDosenDariExcel::dispatchSync(riwayatDari('dosen', array_slice($baris, 0, 2)));

    expect($riwayat->refresh()->berhasil)->toBe(2)->and($riwayat->gagal)->toBe(2)
        ->and(Dosen::count())->toBe(2)
        ->and(Dosen::firstWhere('nidn', '0400000001')->prodi_id)->toBe($this->prodi->id);
});

it('menolak berkas pdf pada input impor dan mengantre berkas valid', function () {
    Queue::fake();
    $this->actingAs($this->pelaku);

    Livewire::test(ImporMahasiswa::class)
        ->callAction('proses', ['berkas' => UploadedFile::fake()->create('daftar.pdf', 10, 'application/pdf')])
        ->assertHasActionErrors(['berkas']);
    expect(RiwayatImpor::count())->toBe(0);

    Livewire::test(ImporMahasiswa::class)
        ->callAction('proses', ['berkas' => UploadedFile::fake()->create('daftar.csv', 1, 'text/csv')])
        ->assertHasNoActionErrors();

    Queue::assertPushed(ImporMahasiswaDariExcel::class);
    expect(RiwayatImpor::firstWhere('jenis', 'mahasiswa'))->not->toBeNull();
});

it('mengunduh templat xlsx langsung tanpa menyimpan berkas', function () {
    $this->actingAs($this->pelaku);

    Livewire::test(ImporDosen::class)
        ->callAction('unduhTemplat')
        ->assertFileDownloaded('templat-impor-dosen.xlsx');
});

it('menolak admin-prodi pada halaman impor', function () {
    $adminProdi = User::factory()->create(['prodi_id' => $this->prodi->id])->assignRole('admin-prodi');
    $this->actingAs($adminProdi);

    $this->get(ImporMahasiswa::getUrl())->assertForbidden();
    $this->get(ImporDosen::getUrl())->assertForbidden();
});

<?php

use App\Actions\Pengguna\ImporPenggunaDariSalinan;
use App\Filament\Admin\Pages\ImporPengguna;
use App\Models\Prodi;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    Filament::setCurrentPanel('admin');
    Notification::fake();
    $this->admin = User::factory()->create()->assignRole('super-admin');
    $this->prodi = Prodi::factory()->create(['kode' => 'PBI']);
});

it('mengurai tempelan Excel (tab) dengan baris judul dan tanpa judul', function () {
    $aksi = app(ImporPenggunaDariSalinan::class);

    $judul = $aksi->urai("Nama\tSurel\tPeran\nBudi\tbudi@unsil.ac.id\tdosen");
    $polos = $aksi->urai('Siti;siti@unsil.ac.id;mahasiswa|dosen;PBI;2210001');

    expect($judul[1])->toMatchArray(['nama' => 'Budi', 'email' => 'budi@unsil.ac.id', 'peran' => 'dosen'])
        ->and($polos[1])->toMatchArray(['nama' => 'Siti', 'peran' => 'mahasiswa|dosen', 'kode_prodi' => 'PBI', 'npm' => '2210001']);
});

it('memeriksa galat per baris tanpa menyimpan apa pun', function () {
    $teks = implode("\n", [
        'Ani,ani@unsil.ac.id,dosen',
        'Dobel,ani@unsil.ac.id,dosen',
        'Tanpa Peran,tp@unsil.ac.id,',
        'Peran Asing,pa@unsil.ac.id,rektor',
        'Prodi Hilang,ph@unsil.ac.id,admin-prodi,XXX',
        'Admin Prodi,ap@unsil.ac.id,admin-prodi',
        'Bos,bos@unsil.ac.id,super-admin',
        ',kosong@unsil.ac.id,dosen',
    ]);

    $hasil = collect(app(ImporPenggunaDariSalinan::class)->periksa($teks))->keyBy('no');

    expect($hasil[1]['status'])->toBe('baru')
        ->and($hasil->except(1)->pluck('status')->unique()->all())->toBe(['galat'])
        ->and($hasil[2]['galat'][0])->toContain('baris 1')
        ->and($hasil[6]['galat'][0])->toContain('kode prodi')
        ->and($hasil[7]['galat'][0])->toContain('super-admin')
        ->and(User::where('email', 'ani@unsil.ac.id')->exists())->toBeFalse();
});

it('membuat dan memperbarui pengguna, mengaitkan prodi, dan memberi sandi awal yang wajib diganti', function () {
    User::factory()->create(['name' => 'Lama', 'email' => 'lama@unsil.ac.id'])->assignRole('mahasiswa');

    $hasil = app(ImporPenggunaDariSalinan::class)->jalankan(
        "Admin Prodi,ap@unsil.ac.id,admin-prodi,PBI\nNama Baru,lama@unsil.ac.id,mahasiswa",
        ['sandi_awal' => 'SandiAwal12345', 'wajib_ganti_sandi' => true],
        $this->admin,
    );

    $ap = User::firstWhere('email', 'ap@unsil.ac.id');
    $lama = User::firstWhere('email', 'lama@unsil.ac.id');

    expect($hasil)->toMatchArray(['dibuat' => 1, 'diubah' => 1, 'gagal' => []])
        ->and($ap->hasRole('admin-prodi'))->toBeTrue()
        ->and($ap->prodi_id)->toBe($this->prodi->id)
        ->and($ap->wajib_ganti_sandi)->toBeTrue()
        ->and(Hash::check('SandiAwal12345', $ap->password))->toBeTrue()
        ->and($lama->name)->toBe('Nama Baru');
});

it('mengirim tautan atur sandi bila tanpa sandi, dan satu baris gagal tidak membatalkan yang lain', function () {
    $hasil = app(ImporPenggunaDariSalinan::class)->jalankan(
        "Rusak,rusak@unsil.ac.id,dosen,,,,,,pendek\nBaik,baik@unsil.ac.id,dosen",
        [],
        $this->admin,
    );

    expect($hasil['dibuat'])->toBe(1)
        ->and($hasil['gagal'])->toHaveCount(1)
        ->and($hasil['gagal'][0]['no'])->toBe(1)
        ->and(User::where('email', 'rusak@unsil.ac.id')->exists())->toBeFalse();

    Notification::assertSentTo(User::firstWhere('email', 'baik@unsil.ac.id'), ResetPassword::class);
});

it('menolak impor oleh pengguna tanpa hak kelola pengguna', function () {
    $biasa = User::factory()->create()->assignRole('admin-fakultas');

    expect(fn () => app(ImporPenggunaDariSalinan::class)->jalankan('A,a@unsil.ac.id,dosen', [], $biasa))
        ->toThrow(AuthorizationException::class);
});

it('menjalankan impor dari halaman admin dan menyembunyikannya dari peran lain', function () {
    $this->actingAs($this->admin);

    Livewire::test(ImporPengguna::class)
        ->fillForm(['teks' => "Dosen Satu,ds@unsil.ac.id,dosen\nDosen Dua,dd@unsil.ac.id,dosen"])
        ->call('periksa')
        ->assertSet('pratinjau.0.status', 'baru')
        ->call('impor')
        ->assertSet('hasil.dibuat', 2);

    $this->actingAs(User::factory()->create()->assignRole('admin-fakultas'));
    expect(ImporPengguna::canAccess())->toBeFalse();
});

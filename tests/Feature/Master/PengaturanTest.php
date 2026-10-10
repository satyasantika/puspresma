<?php

use App\Filament\Admin\Pages\PengaturanSistem;
use App\Models\Aktivitas;
use App\Models\User;
use App\Services\Pengaturan;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Database\Seeders\TingkatSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class, TingkatSeeder::class]);
    Filament::setCurrentPanel('admin');
});

function adminPengaturan(string $peran): User
{
    $user = User::factory()->create()->assignRole($peran);
    $user->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();

    return $user;
}

it('mengembalikan nilai sesuai tipe dan bawaan untuk kunci kosong', function () {
    expect(Pengaturan::ambil('poin_aktif'))->toBeFalse()
        ->and(Pengaturan::ambil('galeri_aktif'))->toBeTrue()
        ->and(Pengaturan::ambil('ambang_negara_internasional'))->toBe(3)
        ->and(Pengaturan::ambil('ambang_kemiripan_duplikat'))->toBe('0.80')
        ->and(Pengaturan::ambil('mode_poin_tim'))->toBe('penuh')
        ->and(Pengaturan::ambil('ambang_pt_nasional', 5))->toBe(5)
        ->and(Pengaturan::ambil('tidak_ada', 'x'))->toBe('x');
});

it('menjalankan seeder secara idempoten tanpa menimpa nilai yang diubah', function () {
    Pengaturan::setel('batas_klaim_bulan', 12);
    $this->seed(PengaturanSeeder::class);

    expect(App\Models\Pengaturan::count())->toBe(count(PengaturanSeeder::daftar()))
        ->and(Pengaturan::ambil('batas_klaim_bulan'))->toBe(12);
});

it('membuang cache dan mencatat nilai lama dan baru saat setel', function () {
    $this->actingAs(adminPengaturan('admin-fakultas'));
    expect(Pengaturan::ambil('verifikasi_prodi_aktif'))->toBeFalse()
        ->and(Cache::has(Pengaturan::KUNCI_CACHE))->toBeTrue();

    Pengaturan::setel('verifikasi_prodi_aktif', true);

    expect(Pengaturan::ambil('verifikasi_prodi_aktif'))->toBeTrue();
    $log = Aktivitas::where('log_name', 'pengaturan')->where('event', 'updated')->latest()->first();
    expect($log->description)->toContain('verifikasi_prodi_aktif')
        ->and($log->attribute_changes['old']['nilai'])->toBe('0')
        ->and($log->attribute_changes['attributes']['nilai'])->toBe('1');
});

it('menyimpan perubahan dari halaman dan langsung berlaku', function () {
    $this->actingAs(adminPengaturan('admin-fakultas'));

    Livewire::test(PengaturanSistem::class)
        ->fillForm(['poin_aktif' => true, 'mode_poin_tim' => 'dibagi', 'rentang_hari_duplikat' => 14])
        ->call('simpan')
        ->assertHasNoFormErrors();

    expect(Pengaturan::ambil('poin_aktif'))->toBeTrue()
        ->and(Pengaturan::ambil('mode_poin_tim'))->toBe('dibagi')
        ->and(Pengaturan::ambil('rentang_hari_duplikat'))->toBe(14);
});

it('menolak ambang di luar rentang', function () {
    $this->actingAs(adminPengaturan('admin-fakultas'));

    Livewire::test(PengaturanSistem::class)
        ->fillForm(['ambang_kemiripan_duplikat' => 0.2, 'rentang_hari_duplikat' => 90])
        ->call('simpan')
        ->assertHasFormErrors(['ambang_kemiripan_duplikat', 'rentang_hari_duplikat']);
});

it('menolak admin-prodi pada halaman pengaturan', function () {
    $this->actingAs(adminPengaturan('admin-prodi'));

    $this->get(PengaturanSistem::getUrl())->assertForbidden();
});

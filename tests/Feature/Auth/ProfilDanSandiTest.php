<?php

use App\Actions\Pengguna\GantiSandi;
use App\Actions\Pengguna\WajibkanGantiSandi;
use App\Filament\Pages\Auth\EditProfil;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Database\Seeders\SuperAdminSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

const SANDI_LAMA = 'SandiLama12345';
const SANDI_BARU = 'SandiBaruAman789';

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
});

function penggunaSandi(string $peran = 'mahasiswa', array $atribut = []): User
{
    $user = User::factory()->create($atribut + ['password' => SANDI_LAMA])->assignRole($peran);
    $user->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();

    return $user;
}

it('mengganti sandi: memeriksa sandi lama, menerapkan kebijakan, dan mencabut kewajiban', function () {
    $user = penggunaSandi(atribut: ['wajib_ganti_sandi' => true]);

    expect(fn () => app(GantiSandi::class)->jalankan($user, 'salah-total', SANDI_BARU))->toThrow(ValidationException::class)
        ->and(fn () => app(GantiSandi::class)->jalankan($user, SANDI_LAMA, 'pendek1'))->toThrow(ValidationException::class)
        ->and(fn () => app(GantiSandi::class)->jalankan($user, SANDI_LAMA, SANDI_LAMA))->toThrow(ValidationException::class);

    app(GantiSandi::class)->jalankan($user, SANDI_LAMA, SANDI_BARU);

    $user->refresh();
    expect(Hash::check(SANDI_BARU, $user->password))->toBeTrue()
        ->and($user->wajib_ganti_sandi)->toBeFalse()
        ->and($user->sandi_diubah_at)->not->toBeNull();
});

it('tidak mencatat kata sandi di log aktivitas', function () {
    $user = penggunaSandi();
    app(GantiSandi::class)->jalankan($user, SANDI_LAMA, SANDI_BARU);

    $log = Activity::where('event', 'sandi_diganti')->firstOrFail();

    expect(json_encode($log->properties))->not->toContain(SANDI_BARU)->not->toContain(SANDI_LAMA);
});

it('mengeluarkan sesi lama setelah kata sandi diganti', function () {
    $user = penggunaSandi('admin-fakultas');
    $hashLama = $user->password;

    // Perangkat lain masih menyimpan hash sandi lama di sesinya.
    $this->actingAs($user)->withSession(['password_hash_web' => $hashLama])->get(Filament::getPanel('admin')->getUrl())->assertSuccessful();

    app(GantiSandi::class)->jalankan($user, SANDI_LAMA, SANDI_BARU);

    $this->withSession(['password_hash_web' => $hashLama])->get(Filament::getPanel('admin')->getUrl())->assertRedirect();
    expect(auth()->guest() || session('password_hash_web') !== $hashLama)->toBeTrue();
});

it('mengubah profil dan sandi lewat halaman profil, lalu kembali ke beranda', function () {
    Filament::setCurrentPanel('portal');
    $user = penggunaSandi();
    $this->actingAs($user);

    Livewire::test(EditProfil::class)
        ->fillForm(['name' => 'Nama Baru', 'nomor_wa' => '0812-3456-7890'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->refresh()->name)->toBe('Nama Baru')->and($user->nomor_wa)->toBe('0812-3456-7890');

    Livewire::test(EditProfil::class)
        ->fillForm(['sandi_lama' => SANDI_LAMA, 'sandi_baru' => SANDI_BARU, 'sandi_baru_konfirmasi' => SANDI_BARU])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    expect(Hash::check(SANDI_BARU, $user->refresh()->password))->toBeTrue();
});

it('menolak penggantian sandi bila sandi lama salah atau konfirmasi tidak cocok', function () {
    Filament::setCurrentPanel('portal');
    $this->actingAs(penggunaSandi());

    Livewire::test(EditProfil::class)
        ->fillForm(['sandi_lama' => 'salah', 'sandi_baru' => SANDI_BARU, 'sandi_baru_konfirmasi' => 'beda'])
        ->call('save')
        ->assertHasFormErrors(['sandi_baru_konfirmasi']);
});

it('mengarahkan akun wajib-ganti-sandi ke halaman profil dan membiarkan halaman profil terbuka', function () {
    $user = penggunaSandi('admin-fakultas', ['wajib_ganti_sandi' => true]);
    $panel = Filament::getPanel('admin');
    $this->actingAs($user);

    $this->get($panel->getUrl())->assertRedirect($panel->getProfileUrl());
    $this->get($panel->getProfileUrl())->assertSuccessful();
});

it('tidak mengarahkan akun biasa dan membuka kembali setelah sandi diganti', function () {
    $user = penggunaSandi('admin-fakultas', ['wajib_ganti_sandi' => true]);
    $panel = Filament::getPanel('admin');
    $this->actingAs($user);

    app(GantiSandi::class)->jalankan($user, SANDI_LAMA, SANDI_BARU);

    $this->get($panel->getUrl())->assertSuccessful();
});

it('hanya pengelola pengguna yang dapat mewajibkan ganti sandi', function () {
    $sasaran = penggunaSandi();
    $admin = penggunaSandi('super-admin');

    expect(fn () => app(WajibkanGantiSandi::class)->jalankan(collect([$sasaran]), true, penggunaSandi('mahasiswa')))
        ->toThrow(AuthorizationException::class);

    $n = app(WajibkanGantiSandi::class)->jalankan(collect([$sasaran]), true, $admin);

    expect($n)->toBe(1)->and($sasaran->refresh()->wajib_ganti_sandi)->toBeTrue()
        ->and(app(WajibkanGantiSandi::class)->jalankan(collect([$sasaran]), true, $admin))->toBe(0);
});

it('mematikan kewajiban MFA bila MFA_AKTIF=false', function () {
    $admin = penggunaSandi('super-admin');

    config(['puspresma.mfa_aktif' => true]);
    expect($admin->wajibMfa())->toBeTrue();

    config(['puspresma.mfa_aktif' => false]);
    expect($admin->wajibMfa())->toBeFalse();
});

it('membuat super-admin fkip@unsil.ac.id dengan sandi awal dan tanpa menimpa sandi yang sudah diganti', function () {
    config([
        'puspresma.seed_superadmin.email' => 'fkip@unsil.ac.id',
        'puspresma.seed_superadmin.password' => 'siliwangi',
    ]);

    $this->seed(SuperAdminSeeder::class);
    $user = User::firstWhere('email', 'fkip@unsil.ac.id');

    expect($user)->not->toBeNull()
        ->and($user->hasRole('super-admin'))->toBeTrue()
        ->and(Hash::check('siliwangi', $user->password))->toBeTrue();

    $user->update(['password' => SANDI_BARU]);
    $this->seed(SuperAdminSeeder::class);

    expect(Hash::check(SANDI_BARU, $user->refresh()->password))->toBeTrue();
});

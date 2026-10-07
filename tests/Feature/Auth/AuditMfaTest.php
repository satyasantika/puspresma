<?php

use App\Filament\Admin\Resources\ActivityLogResource;
use App\Filament\Admin\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Filament\Pages\Auth\Masuk;
use App\Models\Aktivitas;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    RateLimiter::clear('x');
});

function akunPeran(string $peran, bool $denganMfa = true): User
{
    $user = User::factory()->create(['password' => 'rahasia-123'])->assignRole($peran);

    if ($denganMfa) {
        $user->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();
    }

    return $user;
}

it('menolak login gagal ke-6 dalam semenit dengan pesan throttle', function () {
    Filament::setCurrentPanel('portal');
    $user = akunPeran('mahasiswa');

    foreach (range(1, 5) as $i) {
        Livewire::test(Masuk::class)
            ->fillForm(['email' => $user->email, 'password' => 'salah'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);
    }

    // Percobaan ke-6 ditolak walau kata sandi benar.
    Livewire::test(Masuk::class)
        ->fillForm(['email' => $user->email, 'password' => 'rahasia-123'])
        ->call('authenticate')
        ->assertHasFormErrors(['email'])
        ->assertSee('Terlalu banyak percobaan masuk');

    $this->assertGuest();
});

it('mencatat login berhasil, gagal, dan logout tanpa memuat kata sandi', function () {
    Filament::setCurrentPanel('portal');
    $user = akunPeran('mahasiswa');

    Livewire::test(Masuk::class)
        ->fillForm(['email' => $user->email, 'password' => 'sandi-salah-xyz'])
        ->call('authenticate');
    Livewire::test(Masuk::class)
        ->fillForm(['email' => $user->email, 'password' => 'rahasia-123'])
        ->call('authenticate');
    auth()->logout();
    event(new Logout('web', $user));

    $log = Aktivitas::where('log_name', 'auth')->get();

    expect($log->pluck('event')->all())->toContain('login_gagal', 'login', 'logout')
        ->and($log->every(fn ($l) => isset($l->properties['ip'], $l->properties['user_agent'])))->toBeTrue()
        ->and(json_encode(Aktivitas::all()->toArray()))
        ->not->toContain('sandi-salah-xyz')->not->toContain('rahasia-123')
        ->not->toContain('JBSWY3DPEHPK3PXP');
});

it('tidak mencatat rahasia MFA dan kata sandi pada log perubahan pengguna', function () {
    $pelaku = akunPeran('super-admin');
    $this->actingAs($pelaku);

    $target = User::factory()->create();
    $target->update(['name' => 'Nama Baru']);
    $target->forceFill(['password' => 'sandi-baru-rahasia', 'app_authentication_secret' => 'RAHASIAMFA234567'])->save();
    $target->assignRole('dosen');

    $json = json_encode(Aktivitas::all()->toArray());

    expect($json)->toContain('Nama Baru')->toContain('peran_ditambah')
        ->not->toContain('sandi-baru-rahasia')->not->toContain('RAHASIAMFA234567')
        ->not->toContain('password')->not->toContain('app_authentication');
});

it('mengarahkan admin-fakultas dan super-admin tanpa MFA ke pengaturan MFA', function (string $peran) {
    $this->actingAs(akunPeran($peran, denganMfa: false))
        ->get('/admin')
        ->assertRedirect(Filament::getPanel('admin')->getSetUpRequiredMultiFactorAuthenticationUrl());
})->with(['admin-fakultas', 'super-admin']);

it('meloloskan admin-fakultas yang sudah memiliki MFA', function () {
    $this->actingAs(akunPeran('admin-fakultas'))->get('/admin')->assertOk();
});

it('tidak memaksa MFA bagi admin-prodi dan pimpinan', function (string $peran) {
    $this->actingAs(akunPeran($peran, denganMfa: false))->get('/admin')->assertOk();
})->with(['admin-prodi', 'pimpinan']);

it('menolak admin-prodi membuka log aktivitas', function () {
    Filament::setCurrentPanel('admin');

    $this->actingAs(akunPeran('admin-prodi'));
    expect(ActivityLogResource::canViewAny())->toBeFalse();
    $this->get('/admin/log-aktivitas')->assertForbidden();
});

it('mengizinkan admin-fakultas membuka log aktivitas', function () {
    Filament::setCurrentPanel('admin');

    $this->actingAs(akunPeran('admin-fakultas'));
    expect(ActivityLogResource::canViewAny())->toBeTrue();
    Livewire::test(ListActivityLogs::class)->assertSuccessful();
    $this->get('/admin/log-aktivitas')->assertOk();
});

it('menampilkan detail log dengan nilai lama dan baru', function () {
    $this->actingAs(akunPeran('super-admin'));
    $target = User::factory()->create(['name' => 'Lama']);
    $target->update(['name' => 'Baru']);

    $log = Aktivitas::where('event', 'updated')->where('subject_id', $target->id)->sole();

    $this->get('/admin/log-aktivitas/'.$log->id)->assertOk()->assertSee('Lama')->assertSee('Baru');
});

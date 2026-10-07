<?php

use App\Actions\Pengguna\HapusPengguna;
use App\Actions\Pengguna\ResetMfaPengguna;
use App\Actions\Pengguna\SimpanPengguna;
use App\Filament\Admin\Resources\UserResource\Pages\CreateUser;
use App\Filament\Admin\Resources\UserResource\Pages\EditUser;
use App\Filament\Admin\Resources\UserResource\Pages\ListUsers;
use App\Models\Aktivitas;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    Filament::setCurrentPanel('admin');
});

function adminDengan(string $peran): User
{
    $user = User::factory()->create()->assignRole($peran);
    $user->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();

    return $user;
}

it('membiarkan super-admin membuat pengguna admin-prodi yang lalu dapat membuka /admin', function () {
    Notification::fake();
    $this->actingAs(adminDengan('super-admin'));

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Admin Prodi Satu',
            'email' => 'prodi1@unsil.ac.id',
            'password' => 'KataSandiKuat123',
            'is_active' => true,
            'peran' => ['admin-prodi'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $baru = User::firstWhere('email', 'prodi1@unsil.ac.id');
    expect($baru->hasRole('admin-prodi'))->toBeTrue()->and($baru->is_active)->toBeTrue();

    auth()->logout();
    $this->actingAs($baru)->get('/admin')->assertOk();
});

it('mengirim tautan reset lewat antrean saat kata sandi dikosongkan', function () {
    Notification::fake();
    $this->actingAs(adminDengan('super-admin'));

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Dosen Baru', 'email' => 'dosen1@unsil.ac.id', 'is_active' => true, 'peran' => ['dosen']])
        ->call('create')
        ->assertHasNoFormErrors();

    $baru = User::firstWhere('email', 'dosen1@unsil.ac.id');
    Notification::assertSentTo($baru, ResetPassword::class, fn (ResetPassword $n) => str_contains($n->url, 'portal') && $n instanceof ShouldQueue);
});

it('menolak email ganda dan kata sandi lemah', function () {
    User::factory()->create(['email' => 'ada@unsil.ac.id']);
    $this->actingAs(adminDengan('super-admin'));

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'X', 'email' => 'ada@unsil.ac.id', 'password' => 'pendek', 'peran' => ['dosen']])
        ->call('create')
        ->assertHasFormErrors(['email', 'password']);
});

it('membiarkan admin-fakultas melihat tetapi tidak mengubah pengguna', function () {
    $fakultas = adminDengan('admin-fakultas');
    $target = User::factory()->create();
    $this->actingAs($fakultas);

    expect($fakultas->can('viewAny', User::class))->toBeTrue()
        ->and($fakultas->can('view', $target))->toBeTrue()
        ->and($fakultas->can('create', User::class))->toBeFalse()
        ->and($fakultas->can('update', $target))->toBeFalse()
        ->and($fakultas->can('delete', $target))->toBeFalse();

    $this->get('/admin/pengguna')->assertOk();
    $this->get('/admin/pengguna/create')->assertForbidden();
    $this->get("/admin/pengguna/{$target->id}/edit")->assertForbidden();
});

it('menolak admin-prodi mengakses daftar pengguna', function () {
    $this->actingAs(adminDengan('admin-prodi'))->get('/admin/pengguna')->assertForbidden();
});

it('menyembunyikan tombol hapus pada baris diri sendiri', function () {
    $super = adminDengan('super-admin');
    $lain = adminDengan('super-admin');
    $this->actingAs($super);

    expect($super->can('delete', $super))->toBeFalse()
        ->and($super->can('delete', $lain))->toBeTrue();

    Livewire::test(ListUsers::class)
        ->assertActionHidden(TestAction::make('delete')->table($super))
        ->assertActionVisible(TestAction::make('delete')->table($lain));
});

it('menolak penghapusan diri sendiri lewat action', function () {
    $super = adminDengan('super-admin');
    $this->actingAs($super);

    expect(fn () => app(HapusPengguna::class)->jalankan($super, $super))
        ->toThrow(AuthorizationException::class);
    expect(User::find($super->id))->not->toBeNull();
});

it('tidak membiarkan peran super-admin terakhir dicabut atau dinonaktifkan', function () {
    $super = adminDengan('super-admin');
    $this->actingAs($super);

    expect(fn () => app(SimpanPengguna::class)->jalankan($super, [
        'name' => $super->name, 'email' => $super->email, 'is_active' => true, 'peran' => ['admin-fakultas'],
    ], $super))->toThrow(ValidationException::class);

    expect($super->fresh()->hasRole('super-admin'))->toBeTrue();

    $lain = adminDengan('super-admin');
    app(SimpanPengguna::class)->jalankan($lain, [
        'name' => $lain->name, 'email' => $lain->email, 'is_active' => true, 'peran' => ['admin-fakultas'],
    ], $super);
    expect($lain->fresh()->hasRole('admin-fakultas'))->toBeTrue();
});

it('mereset MFA hanya oleh super-admin dan mencatatnya di activity_log', function () {
    $super = adminDengan('super-admin');
    $target = adminDengan('admin-fakultas');
    $target->forceFill(['app_authentication_recovery_codes' => ['kode-rahasia-1']])->save();
    $this->actingAs($super);

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('resetMfa')->table($target));

    $target->refresh();
    expect($target->app_authentication_secret)->toBeNull()
        ->and($target->app_authentication_recovery_codes)->toBeNull();

    $log = Aktivitas::where('event', 'mfa_direset')->sole();
    expect($log->causer_id)->toBe($super->id)->and($log->subject_id)->toBe($target->id)
        ->and(json_encode($log->toArray()))->not->toContain('JBSWY3DPEHPK3PXP')->not->toContain('kode-rahasia-1');
});

it('menolak admin-fakultas mereset MFA', function () {
    $fakultas = adminDengan('admin-fakultas');
    $target = adminDengan('admin-fakultas');

    expect(fn () => app(ResetMfaPengguna::class)->jalankan($target, $fakultas))
        ->toThrow(AuthorizationException::class);
    expect($target->fresh()->app_authentication_secret)->not->toBeNull();
});

it('menyimpan perubahan peran lewat halaman edit dan mencatat jejaknya', function () {
    $super = adminDengan('super-admin');
    $target = User::factory()->create()->assignRole('dosen');
    $this->actingAs($super);

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->fillForm(['peran' => ['dosen', 'pimpinan']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->fresh()->hasRole('pimpinan'))->toBeTrue()
        ->and(Aktivitas::where('event', 'peran_ditambah')->where('subject_id', $target->id)->exists())->toBeTrue();
});

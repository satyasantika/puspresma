<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(fn () => $this->seed(PeranDanIzinSeeder::class));

function penggunaDenganPeran(string $peran): User
{
    $user = User::factory()->create()->assignRole($peran);

    // Peran admin wajib MFA; akun uji dianggap sudah menyiapkannya.
    $user->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();

    return $user;
}

it('membuat enam peran dan seluruh permission secara idempoten', function () {
    $this->seed(PeranDanIzinSeeder::class);

    expect(Role::pluck('name')->sort()->values()->all())->toBe(
        ['admin-fakultas', 'admin-prodi', 'dosen', 'mahasiswa', 'pimpinan', 'super-admin'],
    )->and(Permission::count())->toBe(count(PeranDanIzinSeeder::IZIN))
        ->and(Role::findByName('super-admin')->permissions)->toHaveCount(count(PeranDanIzinSeeder::IZIN))
        ->and(Role::findByName('mahasiswa')->permissions->pluck('name')->all())->toBe(['prestasi.buat-milik']);
});

it('memetakan izin admin-fakultas tanpa hak pengguna.kelola', function () {
    $admin = penggunaDenganPeran('admin-fakultas');

    expect($admin->can('prestasi.verifikasi'))->toBeTrue()
        ->and($admin->can('audit.lihat'))->toBeTrue()
        ->and($admin->can('pengguna.kelola'))->toBeFalse()
        ->and($admin->can('horizon.akses'))->toBeFalse();
});

it('mengalihkan mahasiswa dari /admin ke /portal', function () {
    $this->actingAs(penggunaDenganPeran('mahasiswa'))
        ->get('/admin')
        ->assertRedirect(url('portal'));
});

it('mengalihkan admin-prodi dari /portal ke /admin', function () {
    $this->actingAs(penggunaDenganPeran('admin-prodi'))
        ->get('/portal')
        ->assertRedirect(url('admin'));
});

it('mengizinkan tiap peran ke panelnya sendiri', function (string $peran, string $panel) {
    $this->actingAs(penggunaDenganPeran($peran))->get('/'.$panel)->assertOk();
})->with([
    ['super-admin', 'admin'], ['admin-fakultas', 'admin'], ['admin-prodi', 'admin'], ['pimpinan', 'admin'],
    ['mahasiswa', 'portal'], ['dosen', 'portal'],
]);

it('menolak panel bagi pengguna nonaktif', function () {
    $user = penggunaDenganPeran('admin-fakultas');
    $user->update(['is_active' => false]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('hanya super-admin yang membuka horizon', function () {
    $this->get('/horizon')->assertForbidden();
    $this->actingAs(penggunaDenganPeran('admin-fakultas'))->get('/horizon')->assertForbidden();
    $this->actingAs(penggunaDenganPeran('mahasiswa'))->get('/horizon')->assertForbidden();
    $this->actingAs(penggunaDenganPeran('super-admin'))->get('/horizon')->assertOk();
});

it('menolak super-admin pada ability verifikasi prestasi', function (string $ability) {
    $super = penggunaDenganPeran('super-admin');

    expect(Gate::forUser($super)->denies($ability))->toBeTrue()
        ->and(Gate::forUser($super)->allows('pengguna.kelola'))->toBeTrue();
})->with(['verifikasi', 'loloskanProdi', 'bukaVerifikasi', 'kembalikan', 'tolak', 'gantiTautan', 'tinjauDuplikat']);

it('membuat super-admin dari env dengan kata sandi acak sekali', function () {
    config(['puspresma.seed_superadmin' => ['email' => 'root@unsil.ac.id', 'name' => 'Root']]);

    $this->seed(SuperAdminSeeder::class);
    $this->seed(SuperAdminSeeder::class);

    $user = User::where('email', 'root@unsil.ac.id')->sole();
    expect($user->hasRole('super-admin'))->toBeTrue()
        ->and($user->name)->toBe('Root')
        ->and(User::count())->toBe(1);
});

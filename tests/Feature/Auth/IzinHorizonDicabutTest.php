<?php

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\DB;

function migrasiIzinHorizon(): object
{
    return require database_path('migrations/2026_10_16_100000_hapus_izin_horizon.php');
}

it('tidak lagi mendefinisikan izin horizon.akses di seeder', function () {
    expect(PeranDanIzinSeeder::IZIN)->not->toContain('horizon.akses');

    $this->seed(PeranDanIzinSeeder::class);

    expect(Permission::where('name', 'horizon.akses')->exists())->toBeFalse();
});

it('menghapus izin horizon.akses beserta relasi peran dari data lama', function () {
    $izin = Permission::create(['name' => 'horizon.akses', 'guard_name' => 'web']);
    $peran = Role::create(['name' => 'peran-uji-horizon', 'guard_name' => 'web']);
    $peran->givePermissionTo($izin);

    expect(DB::table('role_has_permissions')->where('permission_id', $izin->id)->count())->toBe(1);

    migrasiIzinHorizon()->up();

    expect(Permission::where('name', 'horizon.akses')->exists())->toBeFalse()
        ->and(DB::table('role_has_permissions')->where('permission_id', $izin->id)->count())->toBe(0)
        ->and(Role::where('name', 'peran-uji-horizon')->exists())->toBeTrue();
});

it('aman dijalankan ulang dan down() mengembalikan izin tanpa relasi', function () {
    migrasiIzinHorizon()->up();
    migrasiIzinHorizon()->up();

    expect(Permission::where('name', 'horizon.akses')->exists())->toBeFalse();

    migrasiIzinHorizon()->down();

    expect(Permission::where('name', 'horizon.akses')->count())->toBe(1);
});

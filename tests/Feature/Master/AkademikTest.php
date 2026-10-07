<?php

use App\Enums\StatusMahasiswa;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\User;
use App\Support\ProdiScope;
use Database\Seeders\PeranDanIzinSeeder;
use Database\Seeders\ProdiSeeder;
use Illuminate\Database\QueryException;

it('menghubungkan relasi prodi, mahasiswa, dosen, dan user', function () {
    $prodi = Prodi::factory()->create();
    $user = User::factory()->create(['prodi_id' => $prodi->id]);
    $mhs = Mahasiswa::factory()->for($prodi)->create(['user_id' => $user->id]);
    $dsn = Dosen::factory()->for($prodi)->create();

    expect($prodi->mahasiswa)->toHaveCount(1)
        ->and($prodi->dosen)->toHaveCount(1)
        ->and($mhs->prodi->is($prodi))->toBeTrue()
        ->and($mhs->user->is($user))->toBeTrue()
        ->and($user->mahasiswa->is($mhs))->toBeTrue()
        ->and($user->prodi->is($prodi))->toBeTrue()
        ->and($dsn->prodi->is($prodi))->toBeTrue()
        ->and($mhs->status_mahasiswa)->toBe(StatusMahasiswa::Aktif);
});

it('menyusun nama lengkap dosen dengan gelar', function () {
    $dsn = Dosen::factory()->make(['gelar_depan' => 'Dr.', 'nama' => 'Siti Aminah', 'gelar_belakang' => 'M.Pd.']);

    expect($dsn->nama_lengkap)->toBe('Dr. Siti Aminah, M.Pd.');
});

it('menolak NPM ganda', function () {
    Mahasiswa::factory()->create(['npm' => '2100000001']);

    expect(fn () => Mahasiswa::factory()->create(['npm' => '2100000001']))->toThrow(QueryException::class);
});

it('menjalankan ProdiSeeder secara idempoten', function () {
    $this->seed(ProdiSeeder::class);
    $this->seed(ProdiSeeder::class);

    expect(Prodi::count())->toBe(2)
        ->and(Prodi::where('kode', 'CONTOH-A')->value('nama'))->toBe('CONTOH Pendidikan A');
});

it('membatasi admin-prodi ke prodinya dan tidak membatasi admin-fakultas', function () {
    $this->seed(PeranDanIzinSeeder::class);
    $a = Prodi::factory()->create();
    $b = Prodi::factory()->create();
    Mahasiswa::factory()->for($a)->count(2)->create();
    Mahasiswa::factory()->for($b)->create();

    $adminProdi = User::factory()->create(['prodi_id' => $a->id])->assignRole('admin-prodi');
    $adminFak = User::factory()->create()->assignRole('admin-fakultas');
    $tanpaProdi = User::factory()->create()->assignRole('admin-prodi');

    expect(ProdiScope::untuk($adminProdi, Mahasiswa::query())->count())->toBe(2)
        ->and(ProdiScope::untuk($adminFak, Mahasiswa::query())->count())->toBe(3)
        ->and(ProdiScope::untuk($tanpaProdi, Mahasiswa::query())->count())->toBe(0);
});

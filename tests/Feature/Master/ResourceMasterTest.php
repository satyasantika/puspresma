<?php

use App\Filament\Admin\Resources\DosenResource;
use App\Filament\Admin\Resources\DosenResource\Pages\ListDosen;
use App\Filament\Admin\Resources\MahasiswaResource;
use App\Filament\Admin\Resources\MahasiswaResource\Pages\ListMahasiswa;
use App\Filament\Admin\Resources\TingkatResource\Pages\ManageTingkat;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\Tingkat;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    Filament::setCurrentPanel('admin');
});

function penggunaMaster(string $peran, ?Prodi $prodi = null): User
{
    $user = User::factory()->create(['prodi_id' => $prodi?->id])->assignRole($peran);
    $user->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();

    return $user;
}

it('membiarkan admin-fakultas membuat tingkat lewat modal', function () {
    $this->actingAs(penggunaMaster('admin-fakultas'));

    Livewire::test(ManageTingkat::class)
        ->callAction('create', ['kode' => 'kampus', 'nama' => 'Antar Kampus', 'urutan' => 5, 'is_active' => true])
        ->assertHasNoFormErrors();

    expect(Tingkat::where('kode', 'kampus')->value('nama'))->toBe('Antar Kampus');
});

it('tidak menampilkan tombol buat bagi admin-prodi dan menolak aksi', function () {
    $this->actingAs(penggunaMaster('admin-prodi', Prodi::factory()->create()));

    Livewire::test(ManageTingkat::class)
        ->assertActionHidden('create');
    expect(Tingkat::count())->toBe(0);
});

it('membatasi admin-prodi pada mahasiswa prodinya sendiri', function () {
    $a = Prodi::factory()->create();
    $b = Prodi::factory()->create();
    $mhsA = Mahasiswa::factory()->for($a)->count(2)->create();
    $mhsB = Mahasiswa::factory()->for($b)->create();
    $this->actingAs(penggunaMaster('admin-prodi', $a));

    Livewire::test(ListMahasiswa::class)
        ->assertCanSeeTableRecords($mhsA)
        ->assertCanNotSeeTableRecords([$mhsB]);

    $this->get(MahasiswaResource::getUrl('view', ['record' => $mhsA->first()]))->assertOk();
    $this->get(MahasiswaResource::getUrl('view', ['record' => $mhsB]))->assertForbidden();
});

it('membatasi admin-prodi pada dosen prodinya dan menolak IDOR', function () {
    $a = Prodi::factory()->create();
    $b = Prodi::factory()->create();
    $dosenA = Dosen::factory()->for($a)->create();
    $dosenB = Dosen::factory()->for($b)->create();
    $this->actingAs(penggunaMaster('admin-prodi', $a));

    Livewire::test(ListDosen::class)
        ->assertCanSeeTableRecords([$dosenA])
        ->assertCanNotSeeTableRecords([$dosenB]);

    $this->get(DosenResource::getUrl('view', ['record' => $dosenB]))->assertForbidden();
});

it('menyembunyikan kolom surel dari pimpinan dan menampilkannya untuk admin-fakultas', function () {
    Mahasiswa::factory()->create();

    $this->actingAs(penggunaMaster('pimpinan'));
    Livewire::test(ListMahasiswa::class)->assertTableColumnHidden('email');

    $this->actingAs(penggunaMaster('admin-fakultas'));
    Livewire::test(ListMahasiswa::class)->assertTableColumnVisible('email');
});

it('membuat akun mahasiswa dari master dengan peran mahasiswa', function () {
    $mhs = Mahasiswa::factory()->create();
    $this->actingAs(penggunaMaster('admin-fakultas'));

    Livewire::test(ListMahasiswa::class)
        ->callAction(TestAction::make('buatAkun')->table($mhs));

    $mhs->refresh();
    expect($mhs->user_id)->not->toBeNull()
        ->and($mhs->user->hasRole('mahasiswa'))->toBeTrue()
        ->and($mhs->user->email)->toBe($mhs->email)
        ->and($mhs->user->prodi_id)->toBe($mhs->prodi_id);
});

it('membuat akun dosen dengan peran dosen dan menolak admin-prodi', function () {
    $prodi = Prodi::factory()->create();
    $dosen = Dosen::factory()->for($prodi)->create();

    $this->actingAs(penggunaMaster('admin-prodi', $prodi));
    Livewire::test(ListDosen::class)->assertActionHidden(TestAction::make('buatAkun')->table($dosen));

    $this->actingAs(penggunaMaster('admin-fakultas'));
    Livewire::test(ListDosen::class)->callAction(TestAction::make('buatAkun')->table($dosen));

    expect($dosen->refresh()->user->hasRole('dosen'))->toBeTrue();
});

<?php

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Resources\PrestasiResource\Pages\ListPrestasi;
use App\Filament\Portal\Resources\PrestasiSayaResource\Pages\ListPrestasiSaya;
use App\Livewire\Publik\KatalogKompetisi;
use App\Models\Kompetisi;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use App\Models\TautanBerkas;
use App\Models\User;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/** Menghitung kueri selama $kerja berjalan (cache dihangatkan dulu agar yang diukur kueri tetap). */
function hitungKueri(Closure $kerja): int
{
    $jumlah = 0;
    DB::listen(function () use (&$jumlah): void {
        $jumlah++;
    });
    $kerja();

    return $jumlah;
}

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class]);
    Http::fake(['*' => Http::response('', 200)]);
});

it('menjaga antrean verifikasi admin (25 baris) ≤ 15 kueri tanpa lazy loading', function () {
    Filament::setCurrentPanel('admin');
    $admin = User::factory()->create()->assignRole('admin-fakultas');
    Prestasi::factory()->count(25)->diajukan()->create()->each(fn ($p) => TautanBerkas::factory()->untukPrestasi($p)->sertifikat()->create());
    $this->actingAs($admin);

    Livewire::test(ListPrestasi::class);

    $kueri = hitungKueri(fn () => Livewire::test(ListPrestasi::class)->assertSuccessful()->assertCountTableRecords(25));

    expect($kueri)->toBeLessThanOrEqual(15);
});

it('menjaga Prestasi Saya (25 baris) ≤ 15 kueri', function () {
    Filament::setCurrentPanel('portal');
    $mhs = Mahasiswa::factory()->create();
    $user = User::factory()->create(['email' => $mhs->email])->assignRole('mahasiswa');
    $mhs->update(['user_id' => $user->id]);
    Prestasi::factory()->count(25)->for($mhs, 'pengaju')->create();
    $this->actingAs($user);

    $kueri = hitungKueri(fn () => Livewire::test(ListPrestasiSaya::class)->assertSuccessful());

    expect($kueri)->toBeLessThanOrEqual(15);
});

it('menjaga galeri (12 kartu, cache dingin) ≤ 15 kueri dan cache hangat hampir tanpa kueri', function () {
    foreach (range(1, 12) as $_) {
        $p = Prestasi::factory()->terverifikasi()->kelompok()->create();
        PrestasiAnggota::where('prestasi_id', $p->id)->update(['setuju_publikasi' => true]);
        PrestasiAnggota::factory()->create(['prestasi_id' => $p->id]);
        TautanBerkas::factory()->untukPrestasi($p)->fotoPublik()->create();
    }

    $dingin = hitungKueri(fn () => $this->get(route('galeri.index'))->assertOk());
    $hangat = hitungKueri(fn () => $this->get(route('galeri.index'))->assertOk());

    expect($dingin)->toBeLessThanOrEqual(15)->and($hangat)->toBeLessThan($dingin);
});

it('menjaga katalog publik (12 kartu) ≤ 15 kueri', function () {
    Kompetisi::factory()->count(12)->terbit()->create();

    $kueri = hitungKueri(fn () => Livewire::test(KatalogKompetisi::class)->assertSuccessful());

    expect($kueri)->toBeLessThanOrEqual(15);
});

it('menjaga dasbor admin ≤ 15 kueri pada cache dingin', function () {
    Filament::setCurrentPanel('admin');
    $admin = User::factory()->create()->assignRole('pimpinan');
    Prestasi::factory()->count(10)->terverifikasi()->create();
    $this->actingAs($admin);

    $kueri = hitungKueri(fn () => $this->get(Dashboard::getUrl())->assertOk());

    expect($kueri)->toBeLessThanOrEqual(15);
});

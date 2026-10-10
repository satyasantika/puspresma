<?php

use App\Actions\Poin\SimpanMatriksBobot;
use App\Filament\Admin\Pages\MatriksBobotPoin;
use App\Filament\Admin\Resources\BobotPoinResource;
use App\Models\BobotPoin;
use App\Models\Capaian;
use App\Models\JenisKegiatan;
use App\Models\Tingkat;
use App\Models\User;
use App\Services\PenghitungPoin;
use Carbon\CarbonImmutable;
use Database\Seeders\BobotPoinContohSeeder;
use Database\Seeders\CapaianSeeder;
use Database\Seeders\JenisKegiatanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Database\Seeders\TingkatSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, JenisKegiatanSeeder::class, TingkatSeeder::class, CapaianSeeder::class]);
    Filament::setCurrentPanel('admin');
    $this->jenis = JenisKegiatan::firstWhere('kode', 'kompetisi');
    $this->nasional = Tingkat::firstWhere('kode', 'nasional');
    $this->juara1 = Capaian::where('jenis_kegiatan_id', $this->jenis->id)->firstWhere('kode', 'juara-1');
    $this->admin = User::factory()->create()->assignRole('admin-fakultas');
    $this->admin->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();
});

function bobot(string $mulai, ?string $sampai, string $poin): BobotPoin
{
    return BobotPoin::create([
        'jenis_kegiatan_id' => test()->jenis->id, 'tingkat_id' => test()->nasional->id, 'capaian_id' => test()->juara1->id,
        'poin' => $poin, 'berlaku_mulai' => $mulai, 'berlaku_sampai' => $sampai,
    ]);
}

function cariBobot(string $tanggal): ?string
{
    return app(PenghitungPoin::class)->bobot(test()->jenis->id, test()->nasional->id, test()->juara1->id, CarbonImmutable::parse($tanggal));
}

it('memilih bobot menurut periode yang berlaku pada tanggal kegiatan', function () {
    bobot('2025-01-01', '2025-12-31', '30');
    bobot('2026-01-01', null, '40');

    expect(cariBobot('2025-06-01'))->toBe('30.00')
        ->and(cariBobot('2026-03-01'))->toBe('40.00')
        ->and(cariBobot('2024-12-31'))->toBeNull();
});

it('membuang cache bobot saat bobot berubah', function () {
    $b = bobot('2026-01-01', null, '40');
    expect(cariBobot('2026-02-01'))->toBe('40.00')->and(Cache::has(PenghitungPoin::KUNCI_CACHE))->toBeTrue();

    $b->update(['poin' => '45.5']);

    expect(cariBobot('2026-02-01'))->toBe('45.50');
});

it('menutup periode lama saat matriks baru disimpan dan bertipe string desimal', function () {
    bobot('2025-01-01', null, '30');

    $jumlah = app(SimpanMatriksBobot::class)->jalankan(
        $this->jenis->id, CarbonImmutable::parse('2026-01-01'), 'SK Dekan No. 1/2026',
        [$this->juara1->id => [$this->nasional->id => '40', 'kosong' => '']], $this->admin,
    );

    expect($jumlah)->toBe(1)
        ->and(BobotPoin::where('berlaku_mulai', '2025-01-01')->first()->berlaku_sampai->toDateString())->toBe('2025-12-31')
        ->and(BobotPoin::where('berlaku_mulai', '2026-01-01')->first()->berlaku_sampai)->toBeNull()
        ->and(cariBobot('2025-12-31'))->toBe('30.00')
        ->and(cariBobot('2026-01-01'))->toBe('40.00')
        ->and(cariBobot('2026-01-01'))->toBeString();
});

it('menolak dasar penetapan kosong, angka tidak valid, dan periode mundur', function () {
    $aksi = app(SimpanMatriksBobot::class);
    $mulai = CarbonImmutable::parse('2026-01-01');
    $sel = [$this->juara1->id => [$this->nasional->id => '10']];

    expect(fn () => $aksi->jalankan($this->jenis->id, $mulai, ' ', $sel, $this->admin))->toThrow(ValidationException::class);
    expect(fn () => $aksi->jalankan($this->jenis->id, $mulai, 'SK', [$this->juara1->id => [$this->nasional->id => '1.234']], $this->admin))->toThrow(ValidationException::class);

    bobot('2027-01-01', null, '50');
    expect(fn () => $aksi->jalankan($this->jenis->id, $mulai, 'SK', $sel, $this->admin))->toThrow(ValidationException::class);
});

it('menolak pelaku tanpa izin poin.kelola pada action', function () {
    $adminProdi = User::factory()->create()->assignRole('admin-prodi');

    expect(fn () => app(SimpanMatriksBobot::class)->jalankan(
        $this->jenis->id, CarbonImmutable::parse('2026-01-01'), 'SK', [$this->juara1->id => [$this->nasional->id => '10']], $adminProdi,
    ))->toThrow(AuthorizationException::class);
});

it('menyimpan matriks dari halaman Filament', function () {
    $this->actingAs($this->admin);

    Livewire::test(MatriksBobotPoin::class)
        ->fillForm(['jenis_kegiatan_id' => $this->jenis->id, 'berlaku_mulai' => '2026-01-01', 'dasar_penetapan' => 'SK Dekan 1'])
        ->set('matriks', [$this->juara1->id => [$this->nasional->id => '40']])
        ->call('simpan')
        ->assertHasNoFormErrors();

    expect(cariBobot('2026-05-01'))->toBe('40.00');
});

it('menolak admin-prodi pada matriks dan resource bobot poin', function () {
    $this->actingAs(User::factory()->create()->assignRole('admin-prodi'));

    $this->get(MatriksBobotPoin::getUrl())->assertForbidden();
    $this->get(BobotPoinResource::getUrl())->assertForbidden();
});

it('memuat contoh bobot dari seeder manual tanpa mendaftarkannya di DatabaseSeeder', function () {
    $this->seed(BobotPoinContohSeeder::class);

    expect(cariBobot('2026-06-01'))->toBe('40.00')
        ->and(file_get_contents(database_path('seeders/DatabaseSeeder.php')))->not->toContain('BobotPoinContohSeeder');
});

it('tidak memakai kolom float atau double pada migrasi bobot poin', function () {
    $isi = file_get_contents(database_path('migrations/2026_10_09_140000_create_bobot_poin_table.php'));

    expect($isi)->not->toMatch('/->(float|double)\(/')->and($isi)->toContain("decimal('poin', 8, 2)");
});

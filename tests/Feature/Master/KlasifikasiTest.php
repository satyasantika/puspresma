<?php

use App\Models\JenisKegiatan;
use App\Models\Tingkat;
use App\Services\OpsiMaster;
use Database\Seeders\CapaianSeeder;
use Database\Seeders\JenisKegiatanSeeder;
use Database\Seeders\KategoriLombaSeeder;
use Database\Seeders\TingkatSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    $this->seed([JenisKegiatanSeeder::class, KategoriLombaSeeder::class, TingkatSeeder::class, CapaianSeeder::class]);
});

it('membuat empat tingkat dari seeder secara idempoten', function () {
    $this->seed(TingkatSeeder::class);

    expect(Tingkat::count())->toBe(4)
        ->and(array_values(app(OpsiMaster::class)->tingkat()))->toBe([
            'Lokal/Perguruan Tinggi', 'Wilayah/Provinsi', 'Nasional', 'Internasional',
        ]);
});

it('tidak memunculkan capaian rekognisi pada capaian kompetisi', function () {
    $kompetisi = JenisKegiatan::where('kode', 'kompetisi')->value('id');
    $opsi = app(OpsiMaster::class)->capaian($kompetisi);

    expect($opsi)->toHaveCount(9)
        ->and($opsi)->toContain('Juara 1')
        ->and($opsi)->not->toContain('Narasumber');
});

it('membatasi kategori lomba per jenis kegiatan', function () {
    $rekognisi = JenisKegiatan::where('kode', 'rekognisi')->value('id');

    expect(app(OpsiMaster::class)->kategoriLomba($rekognisi))->toHaveCount(1)
        ->and(app(OpsiMaster::class)->kategoriLomba())->toHaveCount(12);
});

it('membuang cache ketika master berubah', function () {
    $opsi = app(OpsiMaster::class);
    $opsi->tingkat();
    expect(Cache::has('master:tingkat'))->toBeTrue();

    Tingkat::where('kode', 'lokal')->first()->update(['nama' => 'Perguruan Tinggi']);

    expect(Cache::has('master:tingkat'))->toBeFalse()
        ->and($opsi->tingkat())->toContain('Perguruan Tinggi');
});

it('menyembunyikan master nonaktif dari opsi', function () {
    $opsi = app(OpsiMaster::class);
    $opsi->tingkat();
    Tingkat::where('kode', 'wilayah')->first()->update(['is_active' => false]);

    expect($opsi->tingkat())->toHaveCount(3);
});

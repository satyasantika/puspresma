<?php

/**
 * Profil Ringan (STANDAR-TEKNIS §1b): contoh env tidak boleh kembali ke Redis,
 * kode tidak memakai Redis, dan antrean hanya default dan berat.
 */
dataset('contoh env', ['.env.example', '.env.production.example']);

it('memakai driver database pada contoh env', function (string $berkas) {
    $isi = file_get_contents(base_path($berkas));

    expect($isi)
        ->toContain("\nSESSION_DRIVER=database\n")
        ->toContain("\nQUEUE_CONNECTION=database\n")
        ->toContain("\nCACHE_STORE=database\n")
        ->toContain("\nAPP_KODE=puspresma\n")
        ->not->toMatch('/^REDIS_/m')
        ->not->toContain('_DRIVER=redis')
        ->not->toContain('QUEUE_CONNECTION=redis')
        ->not->toContain('CACHE_STORE=redis');
})->with('contoh env');

it('hanya memakai antrean default dan berat', function () {
    $diizinkan = ['default', 'berat'];

    foreach (config('puspresma.antrean') as $nama) {
        expect($nama)->toBeIn($diizinkan);
    }

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())) as $berkas) {
        if ($berkas->getExtension() !== 'php') {
            continue;
        }

        preg_match_all('/onQueue\(\s*[\'"]([^\'"]+)[\'"]/', file_get_contents($berkas->getPathname()), $cocok);

        foreach ($cocok[1] as $nama) {
            expect($nama)->toBeIn($diizinkan, str_replace(app_path().'/', '', $berkas->getPathname())." memakai antrean '{$nama}'");
        }
    }
});

it('tidak memakai Redis di kode aplikasi', function () {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())) as $berkas) {
        if ($berkas->getExtension() !== 'php') {
            continue;
        }

        expect(file_get_contents($berkas->getPathname()))
            ->not->toContain('Redis::')
            ->not->toContain('Illuminate\\Support\\Facades\\Redis');
    }
});

it('tidak memasang Horizon', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true);

    expect($composer['require'])->not->toHaveKey('laravel/horizon')
        ->and(file_exists(config_path('horizon.php')))->toBeFalse()
        ->and(file_exists(app_path('Providers/HorizonServiceProvider.php')))->toBeFalse()
        ->and(file_get_contents(base_path('bootstrap/providers.php')))->not->toContain('Horizon');
});

it('tidak merujuk kelas Horizon di kode aplikasi', function () {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())) as $berkas) {
        if ($berkas->getExtension() === 'php') {
            expect(file_get_contents($berkas->getPathname()))->not->toContain('Laravel\\Horizon');
        }
    }
});

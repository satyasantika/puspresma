<?php

use Illuminate\Support\Facades\Gate;

/**
 * Setiap Resource Filament wajib memakai Policy (CLAUDE.md: otorisasi di Policy).
 * Kelas Resource dicari di app/Filament/<Panel>/Resources/*Resource.php.
 */
it('memberi setiap Resource Filament model yang terdaftar Policy-nya', function () {
    $berkas = glob(app_path('Filament/*/Resources/*Resource.php'));

    expect($berkas)->not->toBeEmpty();

    foreach ($berkas as $path) {
        $kelas = 'App\\'.str_replace(['/', '.php'], ['\\', ''], substr($path, strlen(app_path()) + 1));
        $model = $kelas::getModel();

        expect(Gate::getPolicyFor($model))
            ->not->toBeNull("{$kelas}: model {$model} belum punya Policy");
    }
});

it('tidak menaruh Resource Filament di luar Policy-checked namespace panel', function () {
    foreach (glob(app_path('Filament/Resources/*')) as $path) {
        $this->fail("Resource harus berada di app/Filament/Admin atau app/Filament/Portal: {$path}");
    }

    expect(true)->toBeTrue();
});

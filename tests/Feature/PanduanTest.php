<?php

use Database\Seeders\PanduanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Spatie\Permission\Models\Role;

function berkasPanduan(string $nama): string
{
    return public_path("panduan/{$nama}");
}

const PERAN_PUBLIK = ['mahasiswa', 'dosen', 'admin-prodi', 'admin-fakultas', 'pimpinan'];

it('menyediakan halaman HTML panduan untuk setiap peran publik dan mencantumkannya di indeks', function (string $peran) {
    expect(berkasPanduan("{$peran}.html"))->toBeFile()
        ->and(file_get_contents(berkasPanduan('index.html')))->toContain("href=\"{$peran}.html\"");
})->with(PERAN_PUBLIK);

it('tidak memublikasikan panduan super-admin', function () {
    expect(berkasPanduan('super-admin.html'))->not->toBeFile()
        ->and(is_dir(berkasPanduan('img/super-admin')))->toBeFalse()
        ->and(file_get_contents(berkasPanduan('index.html')))->not->toContain('super-admin');
});

it('memakai peran yang sama dengan seeder peran', function () {
    $this->seed(PeranDanIzinSeeder::class);

    expect(Role::pluck('name')->sort()->values()->all())->toBe(collect(PanduanSeeder::PERAN)->sort()->values()->all());
});

it('menunjuk hanya gambar yang ada', function () {
    $jumlah = 0;
    foreach (glob(berkasPanduan('*.html')) as $html) {
        preg_match_all('/<img[^>]+src="([^"]+)"/', file_get_contents($html), $cocok);
        foreach ($cocok[1] as $src) {
            expect(berkasPanduan($src))->toBeFile();
            $jumlah++;
        }
    }

    expect($jumlah)->toBeGreaterThan(0);
});

it('tidak memakai href/src berawalan "/" atau http://localhost', function () {
    foreach (glob(berkasPanduan('*.html')) as $html) {
        $isi = file_get_contents($html);

        expect(preg_match('/(?:href|src|action)="\//', $isi))->toBe(0, basename($html).' memakai URL absolut-path')
            ->and($isi)->not->toContain('http://localhost');
    }
});

it('menautkan panduan dari landing page', function () {
    $this->get(route('beranda'))->assertOk()->assertSee('panduan/index.html', false)->assertSee('Panduan pengguna bergambar');
});

it('menjaga ukuran total panduan ≤ 15 MB', function () {
    $total = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(public_path('panduan'), FilesystemIterator::SKIP_DOTS)) as $berkas) {
        $total += $berkas->getSize();
    }

    expect($total)->toBeLessThanOrEqual(15 * 1024 * 1024);
});

it('menolak PanduanSeeder di luar lingkungan lokal', function (string $lingkungan) {
    app()->detectEnvironment(fn () => $lingkungan);

    expect(fn () => (new PanduanSeeder)->run())->toThrow(RuntimeException::class, 'APP_ENV=local');
})->with(['production', 'testing']);

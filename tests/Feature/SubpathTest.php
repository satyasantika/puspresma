<?php

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

// Meniru reverse proxy yang sudah memotong awalan /puspresma sebelum meneruskan ke aplikasi.
const LANGSUNG = 'https://supportfkip.unsil.ac.id';

beforeEach(function () {
    config([
        'app.url' => 'https://supportfkip.unsil.ac.id/puspresma',
        'app.asset_url' => 'https://supportfkip.unsil.ac.id/puspresma',
        'session.path' => '/puspresma',
        'session.cookie' => 'puspresma_session',
    ]);

    app()->getProvider(AppServiceProvider::class)->boot();
});

afterEach(function () {
    URL::forceRootUrl(null);
    URL::forceScheme(null);
});

it('membangkitkan url dengan awalan subpath', function () {
    expect(url('/'))->toBe('https://supportfkip.unsil.ac.id/puspresma')
        ->and(asset('build/app.js'))->toStartWith('https://supportfkip.unsil.ac.id/puspresma/');
});

it('memasang cookie sesi puspresma_session ber-path /puspresma', function () {
    $cookie = collect($this->get(LANGSUNG.'/')->headers->getCookies())
        ->first(fn ($c) => $c->getName() === 'puspresma_session');

    expect($cookie)->not->toBeNull()
        ->and($cookie->getPath())->toBe('/puspresma');
});

it('mengalihkan tamu ke login tetap di bawah subpath', function () {
    // Cukup menamai satu rute 'login' untuk menguji pengalihan tamu.
    Route::middleware('web')->get('/masuk', fn () => 'masuk')->name('login');
    Route::middleware(['web', 'auth'])->get('/uji-terlindungi', fn () => 'rahasia');
    Route::getRoutes()->refreshNameLookups();

    $this->get(LANGSUNG.'/uji-terlindungi')->assertRedirect('https://supportfkip.unsil.ac.id/puspresma/masuk');
});

it('mempercayai header X-Forwarded-Prefix dari proxy', function () {
    $this->withHeaders(['X-Forwarded-Prefix' => '/puspresma'])
        ->get(LANGSUNG.'/')->assertOk();

    expect(url('/uji'))->toBe('https://supportfkip.unsil.ac.id/puspresma/uji');
});

it('menurunkan path cookie sesi dari APP_URL bila SESSION_PATH kosong', function (string $appUrl, string $diharapkan) {
    $kunci = ['APP_URL' => $appUrl, 'SESSION_PATH' => null];
    $lama = [];

    foreach ($kunci as $nama => $nilai) {
        $lama[$nama] = [$_ENV[$nama] ?? null, $_SERVER[$nama] ?? null, getenv($nama)];
        $nilai === null ? putenv($nama) : putenv("{$nama}={$nilai}");
        $nilai === null ? [$_ENV[$nama], $_SERVER[$nama]] = [null, null] : [$_ENV[$nama], $_SERVER[$nama]] = [$nilai, $nilai];
        if ($nilai === null) {
            unset($_ENV[$nama], $_SERVER[$nama]);
        }
    }

    try {
        $config = require config_path('session.php');
    } finally {
        foreach ($lama as $nama => [$env, $server, $proses]) {
            $env === null ? null : $_ENV[$nama] = $env;
            $server === null ? null : $_SERVER[$nama] = $server;
            $proses === false ? putenv($nama) : putenv("{$nama}={$proses}");
        }
    }

    expect($config['path'])->toBe($diharapkan);
})->with([
    'subpath lain' => ['https://supportfkip.unsil.ac.id/siman', '/siman'],
    'akar domain' => ['http://localhost:8023', '/'],
]);

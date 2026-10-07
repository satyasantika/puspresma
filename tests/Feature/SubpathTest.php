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
    // Rute login sesungguhnya dibuat di F2; di sini cukup menamai satu rute 'login'.
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

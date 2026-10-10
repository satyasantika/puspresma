<?php

use App\Support\HalamanGalat;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware('web')->get('/uji-galat/{kode}', function (int $kode) {
        $header = $kode === 429 || $kode === 503 ? ['Retry-After' => '45'] : [];

        abort($kode, '', $header);
    });
    Route::getRoutes()->refreshNameLookups();
});

it('menampilkan halaman bertema untuk setiap kode galat yang didukung', function (int $kode) {
    $isi = HalamanGalat::untuk($kode);

    $respons = $this->get("/uji-galat/{$kode}");

    $respons->assertStatus($kode)
        ->assertSee($isi['judul'], false)
        ->assertSee('Puspresma', false)
        ->assertSee((string) $kode, false)
        ->assertSee('Ke beranda', false)
        ->assertDontSee('Whoops', false)
        ->assertDontSee('vendor/laravel', false);
})->with(HalamanGalat::kodeTerdaftar());

it('memakai halaman cadangan untuk kode yang tidak terdaftar', function () {
    $this->get('/uji-galat/418')->assertStatus(418)->assertSee('Permintaan tidak dapat dilayani', false);
    $this->get('/uji-galat/507')->assertStatus(507)->assertSee('Layanan bermasalah', false);
});

it('menawarkan masuk pada 401 dan muat ulang pada 419', function () {
    $this->get('/uji-galat/401')->assertSee('Masuk', false)->assertSee('/masuk', false);
    $this->get('/uji-galat/419')->assertSee('Muat ulang', false);
});

it('memuat ulang halaman pemeliharaan otomatis dan menghormati Retry-After', function () {
    $this->get('/uji-galat/503')->assertSee('<meta http-equiv="refresh" content="45">', false);
    $this->get('/uji-galat/429')->assertSee('sekitar 45 detik', false);
});

it('tetap menjawab JSON untuk klien API', function () {
    $this->getJson('/uji-galat/404')->assertNotFound()->assertJsonStructure(['message']);
});

it('halaman 404 untuk rute yang tidak ada memakai tema aplikasi', function () {
    $this->get('/tidak-ada-halaman-ini')->assertNotFound()->assertSee('Halaman tidak ditemukan', false);
});

it('tidak bergantung pada Vite atau skrip pihak ketiga agar tetap tampil saat sistem bermasalah', function () {
    $isi = file_get_contents(resource_path('views/errors/tampil.blade.php'));

    expect($isi)->not->toContain('@vite')->not->toContain('<script src')->not->toContain('http://')->not->toContain('https://');
});

it('menyediakan berkas khusus untuk setiap kode yang punya bawaan framework', function (int $kode) {
    expect(file_exists(resource_path("views/errors/{$kode}.blade.php")))->toBeTrue();
})->with([401, 402, 403, 404, 419, 429, 500, 503]);

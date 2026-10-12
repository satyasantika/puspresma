<?php

use App\Support\AwalanSubpath;

it('menurunkan awalan dari APP_URL', function (?string $url, string $awalan) {
    expect(AwalanSubpath::dariUrl($url))->toBe($awalan);
})->with([
    'subpath' => ['https://supportfkip.unsil.ac.id/puspresma', '/puspresma'],
    'garis miring akhir' => ['https://supportfkip.unsil.ac.id/siman/', '/siman'],
    'dua tingkat' => ['https://contoh.id/a/b', '/a/b'],
    'akar domain' => ['https://supportfkip.unsil.ac.id', ''],
    'akar dengan garis miring' => ['http://localhost:8032/', ''],
    'kosong' => [null, ''],
]);

it('membuang awalan yang diteruskan utuh oleh proxy', function (string $uri, string $hasil) {
    $server = AwalanSubpath::bersihkan(['REQUEST_URI' => $uri, 'PATH_INFO' => $uri], '/puspresma');

    expect($server['REQUEST_URI'])->toBe($hasil)->and($server['PATH_INFO'])->toBe($hasil);
})->with([
    'halaman' => ['/puspresma/galeri', '/galeri'],
    'dengan kueri' => ['/puspresma/galeri?halaman=2', '/galeri?halaman=2'],
    'akar' => ['/puspresma', '/'],
    'akar dengan kueri' => ['/puspresma?x=1', '/?x=1'],
    'sudah dipotong proxy' => ['/galeri', '/galeri'],
    'bukan awalan penuh' => ['/puspresmax/galeri', '/puspresmax/galeri'],
]);

it('tidak mengubah permintaan bila aplikasi berada di akar domain', function () {
    $server = ['REQUEST_URI' => '/puspresma/galeri'];

    expect(AwalanSubpath::bersihkan($server, ''))->toBe($server);
});

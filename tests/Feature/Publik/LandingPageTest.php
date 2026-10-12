<?php

use App\Models\Kompetisi;
use Database\Seeders\PengaturanSeeder;
use Illuminate\Support\Facades\URL;

beforeEach(fn () => $this->seed(PengaturanSeeder::class));

it('menampilkan landing page yang menjelaskan sistem dan mengajak masuk', function () {
    $this->get(route('beranda'))
        ->assertOk()
        ->assertSee('Setiap prestasi mahasiswa', false)
        ->assertSee('Bagaimana Puspresma bekerja')
        ->assertSee('Ajukan')->assertSee('Verifikasi')->assertSee('Terbit')->assertSee('Laporkan')
        ->assertSee('Galeri berbasis persetujuan')
        ->assertSee(route('login'), false)
        ->assertSee(route('galeri.index'), false);
});

it('menaut panduan bergambar untuk setiap peran kecuali super-admin', function () {
    $halaman = $this->get(route('beranda'))->assertOk();

    foreach (['mahasiswa', 'dosen', 'admin-prodi', 'admin-fakultas', 'pimpinan'] as $peran) {
        $halaman->assertSee("panduan/{$peran}.html", false);
    }

    $halaman->assertDontSee('super-admin.html', false)->assertDontSee('Super Admin');
});

it('menampilkan statistik publik hanya dari data yang sudah publik', function () {
    Kompetisi::factory()->count(2)->terbit()->create(['tenggat_pendaftaran' => now()->addWeek()]);
    Kompetisi::factory()->terbit()->create(['tenggat_pendaftaran' => now()->subWeek()]);

    $this->get(route('beranda'))->assertOk()->assertSee('Kompetisi dibuka')->assertSeeInOrder(['Kompetisi dibuka', '2']);
});

it('tidak membocorkan data pribadi di landing page', function () {
    $isi = $this->get(route('beranda'))->assertOk()->getContent();

    expect($isi)->not->toMatch('/\b\d{8,}\b/')->not->toContain('@unsil.ac.id');
});

it('memakai URL relatif aplikasi (subpath) untuk semua tautan internal', function () {
    config(['app.url' => 'https://supportfkip.unsil.ac.id/puspresma']);
    URL::forceRootUrl('https://supportfkip.unsil.ac.id/puspresma');

    $isi = $this->get(route('beranda'))->getContent();

    expect(preg_match('/(?:href|src|action)="\/(?!\/)/', $isi))->toBe(0);

    URL::forceRootUrl(null);
});

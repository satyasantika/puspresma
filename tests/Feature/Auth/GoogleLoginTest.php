<?php

use App\Enums\StatusMahasiswa;
use App\Models\Aktivitas;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as UserGoogle;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    config(['puspresma.google_login' => true]);
});

function googleSebagai(string $email, array $tambahan = []): void
{
    Socialite::fake('google', UserGoogle::fake(array_merge([
        'id' => 'g-'.md5($email), 'name' => 'Nama Google', 'email' => $email, 'email_verified' => true,
    ], $tambahan)));
}

it('mengembalikan 404 pada route Google bila fitur nonaktif dan menyembunyikan tombol', function () {
    config(['puspresma.google_login' => false]);

    $this->get(route('auth.google.redirect'))->assertNotFound();
    $this->get(route('auth.google.callback'))->assertNotFound();
    $this->get(route('filament.admin.auth.login'))->assertOk()->assertDontSee('Masuk dengan Google');
});

it('menampilkan tombol Google di halaman masuk kedua panel bila fitur aktif', function () {
    $this->get(route('filament.admin.auth.login'))->assertOk()->assertSee('Masuk dengan Google')->assertSee(route('auth.google.redirect'), false);
    $this->get(route('filament.portal.auth.login'))->assertOk()->assertSee('Masuk dengan Google');
});

it('mengalihkan ke Google pada route redirect', function () {
    googleSebagai('x@student.unsil.ac.id');

    $this->get(route('auth.google.redirect'))->assertRedirect();
});

it('menolak email gmail dengan pesan bahasa Indonesia', function () {
    googleSebagai('orang@gmail.com');

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('filament.admin.auth.login'))
        ->assertSessionHas('galat_google', fn ($p) => str_contains($p, 'akun Google kampus'));
    expect(User::count())->toBe(0);
});

it('menolak email yang belum diverifikasi Google', function () {
    googleSebagai('2100000001@student.unsil.ac.id', ['email_verified' => false]);
    Mahasiswa::factory()->create(['npm' => '2100000001', 'email' => '2100000001@student.unsil.ac.id']);

    $this->get(route('auth.google.callback'))->assertSessionHas('galat_google', fn ($p) => str_contains($p, 'belum terverifikasi'));
    $this->assertGuest();
});

it('memasukkan mahasiswa terdaftar, memberi peran mahasiswa, dan menautkan master', function () {
    $prodi = Prodi::factory()->create();
    $mhs = Mahasiswa::factory()->for($prodi)->create(['npm' => '2100000001', 'email' => '2100000001@student.unsil.ac.id']);
    googleSebagai('2100000001@student.unsil.ac.id');

    $this->get(route('auth.google.callback'))->assertRedirect(route('filament.portal.pages.dashboard'));

    $user = User::firstWhere('email', '2100000001@student.unsil.ac.id');
    expect($user->hasRole('mahasiswa'))->toBeTrue()
        ->and($user->npm)->toBe('2100000001')
        ->and($user->prodi_id)->toBe($prodi->id)
        ->and($user->google_id)->toBe('g-'.md5('2100000001@student.unsil.ac.id'))
        ->and($mhs->refresh()->user_id)->toBe($user->id);
    $this->assertAuthenticatedAs($user);
    expect(Aktivitas::where('log_name', 'auth')->count())->toBeGreaterThan(0);
});

it('mencocokkan mahasiswa lewat NPM dari bagian lokal surel bila surel belum tercatat', function () {
    Mahasiswa::factory()->create(['npm' => '2100000002', 'email' => null]);
    googleSebagai('2100000002@student.unsil.ac.id');

    $this->get(route('auth.google.callback'));

    $this->assertAuthenticated();
});

it('menolak surel kampus yang tidak ada di master', function () {
    googleSebagai('9999999999@student.unsil.ac.id');

    $this->get(route('auth.google.callback'))->assertSessionHas('galat_google', 'Data Anda belum terdaftar, hubungi admin prodi');
    $this->assertGuest();
    expect(User::count())->toBe(0);
});

it('menolak mahasiswa berstatus lulus', function () {
    Mahasiswa::factory()->create(['npm' => '2100000003', 'email' => '2100000003@student.unsil.ac.id', 'status_mahasiswa' => StatusMahasiswa::Lulus]);
    googleSebagai('2100000003@student.unsil.ac.id');

    $this->get(route('auth.google.callback'))->assertSessionHas('galat_google', fn ($p) => str_contains($p, 'tidak dapat masuk portal'));
    $this->assertGuest();
});

it('memasukkan dosen dari master dengan peran dosen', function () {
    Dosen::factory()->create(['email' => 'dosen.a@unsil.ac.id']);
    googleSebagai('dosen.a@unsil.ac.id');

    $this->get(route('auth.google.callback'))->assertRedirect(route('filament.portal.pages.dashboard'));

    expect(User::firstWhere('email', 'dosen.a@unsil.ac.id')->hasRole('dosen'))->toBeTrue();
});

it('memasukkan staf yang dibuat super-admin tanpa mengubah perannya', function () {
    $staf = User::factory()->create(['email' => 'staf@unsil.ac.id'])->assignRole('pimpinan');
    googleSebagai('staf@unsil.ac.id');

    $this->get(route('auth.google.callback'))->assertRedirect(route('filament.admin.pages.dashboard'));

    expect($staf->refresh()->getRoleNames()->all())->toBe(['pimpinan'])->and($staf->google_id)->not->toBeNull();
});

it('menolak pegawai tak dikenal dan peran wajib MFA', function () {
    googleSebagai('asing@unsil.ac.id');
    $this->get(route('auth.google.callback'))->assertSessionHas('galat_google', fn ($p) => str_contains($p, 'belum terdaftar'));

    User::factory()->create(['email' => 'fakultas@unsil.ac.id'])->assignRole('admin-fakultas');
    googleSebagai('fakultas@unsil.ac.id');
    $this->get(route('auth.google.callback'))->assertSessionHas('galat_google', fn ($p) => str_contains($p, 'wajib MFA'));
    $this->assertGuest();
});

it('menolak akun nonaktif dan akun yang sudah tertaut ke Google lain', function () {
    User::factory()->create(['email' => 'mati@unsil.ac.id', 'is_active' => false])->assignRole('pimpinan');
    googleSebagai('mati@unsil.ac.id');
    $this->get(route('auth.google.callback'))->assertSessionHas('galat_google', 'Akun Anda dinonaktifkan');

    User::factory()->create(['email' => 'lain@unsil.ac.id', 'google_id' => 'google-orang-lain'])->assignRole('pimpinan');
    googleSebagai('lain@unsil.ac.id');
    $this->get(route('auth.google.callback'))->assertSessionHas('galat_google', fn ($p) => str_contains($p, 'akun Google lain'));
    $this->assertGuest();
});

it('membatasi callback 10 kali per menit per IP', function () {
    googleSebagai('orang@gmail.com');

    foreach (range(1, 10) as $_) {
        $this->get(route('auth.google.callback'))->assertRedirect();
    }

    $this->get(route('auth.google.callback'))->assertStatus(429);
});

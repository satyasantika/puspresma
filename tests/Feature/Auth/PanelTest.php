<?php

use App\Filament\Pages\Auth\Masuk;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Livewire\Livewire;

it('menampilkan halaman masuk panel admin dan portal', function (string $path) {
    $this->get($path)->assertOk()->assertSee('Puspresma FKIP Unsil');
})->with(['/admin/login', '/portal/login']);

it('menolak pengguna nonaktif dengan pesan jelas', function (string $panel) {
    Filament\Facades\Filament::setCurrentPanel($panel);
    $user = User::factory()->create(['is_active' => false, 'password' => 'rahasia-123']);

    Livewire::test(Masuk::class)
        ->fillForm(['email' => $user->email, 'password' => 'rahasia-123'])
        ->call('authenticate')
        ->assertHasFormErrors(['email'])
        ->assertSee('Akun Anda dinonaktifkan');

    $this->assertGuest();
})->with(['admin', 'portal']);

it('mengisi last_login_at setelah login berhasil', function () {
    $this->seed(PeranDanIzinSeeder::class);
    $user = User::factory()->create(['password' => 'rahasia-123'])->assignRole('mahasiswa');
    Filament\Facades\Filament::setCurrentPanel('portal');
    expect($user->last_login_at)->toBeNull();

    Livewire::test(Masuk::class)
        ->fillForm(['email' => $user->email, 'password' => 'rahasia-123'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('memiliki kolom tambahan puspresma pada tabel users', function () {
    foreach (['google_id', 'npm', 'nidn', 'nip', 'nomor_wa', 'notif_wa_aktif', 'is_active', 'last_login_at'] as $kolom) {
        expect(Schema::hasColumn('users', $kolom))->toBeTrue($kolom);
    }
});

<?php

use App\Http\Controllers\BantuanController;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Facades\Filament;

beforeEach(fn () => $this->seed(PeranDanIzinSeeder::class));

it('menyediakan panduan markdown untuk setiap peran', function (string $peran) {
    expect(base_path("docs/panduan/{$peran}.md"))->toBeFile();
})->with(BantuanController::PERAN);

it('mengarahkan pengguna ke panduan perannya dan merendernya', function () {
    $dosen = User::factory()->create()->assignRole('dosen');

    $this->actingAs($dosen)->get(route('bantuan.index'))->assertRedirect(route('bantuan.show', 'dosen'));
    $this->actingAs($dosen)->get(route('bantuan.show', 'dosen'))
        ->assertOk()->assertSee('Panduan Dosen Pembimbing')->assertSee('Konfirmasi pembimbingan');
});

it('menolak tamu dan peran yang tidak dikenal', function () {
    $this->get(route('bantuan.show', 'mahasiswa'))->assertRedirect();

    $this->actingAs(User::factory()->create()->assignRole('mahasiswa'))
        ->get(route('bantuan.show', 'tidak-ada'))->assertNotFound();
});

it('menampilkan menu Bantuan di kedua panel', function (string $panel) {
    $item = collect(Filament::getPanel($panel)->getUserMenuItems())->first(fn ($i) => $i->getName() === 'bantuan');

    expect($item)->not->toBeNull()->and($item->getLabel())->toBe('Bantuan');
})->with(['admin', 'portal']);

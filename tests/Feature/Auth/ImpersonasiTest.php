<?php

use App\Actions\Pengguna\GantiSandi;
use App\Filament\Admin\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->admin = User::factory()->create(['email' => 'fkip@unsil.ac.id'])->assignRole('super-admin');
    $this->mahasiswa = User::factory()->create(['name' => 'Mahasiswa Uji'])->assignRole('mahasiswa');
});

function tautanMasuk(User $target, ?User $oleh = null, ?string $adminId = null): string
{
    return URL::temporarySignedRoute('impersonasi.mulai', now()->addMinute(), [
        'pengguna' => $target->getKey(),
        'oleh' => $adminId ?? ($oleh ?? test()->admin)->getKey(),
    ]);
}

it('memungkinkan super-admin masuk sebagai pengguna lain dan mengalihkan ke panelnya', function () {
    $this->actingAs($this->admin);

    $this->get(tautanMasuk($this->mahasiswa))->assertRedirect(Filament::getPanel('portal')->getUrl());

    expect(auth()->id())->toBe($this->mahasiswa->id)
        ->and(session('impersonator_id'))->toBe($this->admin->id);

    $log = Activity::where('event', 'impersonasi_mulai')->firstOrFail();
    expect($log->causer_id)->toBe($this->admin->id)->and($log->subject_id)->toBe($this->mahasiswa->id);
});

it('mengalihkan ke panel admin bila target adalah staf', function () {
    $staf = User::factory()->create()->assignRole('admin-fakultas');
    $this->actingAs($this->admin);

    $this->get(tautanMasuk($staf))->assertRedirect(Filament::getPanel('admin')->getUrl());
});

it('tidak mengubah last_login_at atau mencatat login palsu', function () {
    $this->actingAs($this->admin);

    $this->get(tautanMasuk($this->mahasiswa));
    $this->post(route('impersonasi.akhiri'));

    expect($this->mahasiswa->refresh()->last_login_at)->toBeNull()
        ->and(Activity::where('log_name', 'auth')->where('event', 'login')->count())->toBe(0);
});

it('menolak tautan tanpa tanda tangan, kedaluwarsa, atau milik super-admin lain', function () {
    $this->actingAs($this->admin);

    $this->get(route('impersonasi.mulai', ['pengguna' => $this->mahasiswa->id, 'oleh' => $this->admin->id]))->assertForbidden();

    $lain = User::factory()->create()->assignRole('super-admin');
    $this->get(tautanMasuk($this->mahasiswa, adminId: $lain->id))->assertForbidden();

    $kedaluwarsa = URL::temporarySignedRoute('impersonasi.mulai', now()->subSecond(), ['pengguna' => $this->mahasiswa->id, 'oleh' => $this->admin->id]);
    $this->get($kedaluwarsa)->assertForbidden();

    expect(auth()->id())->toBe($this->admin->id);
});

it('tidak mengizinkan peran selain super-admin, diri sendiri, akun nonaktif, atau penyamaran bersarang', function () {
    $fakultas = User::factory()->create()->assignRole('admin-fakultas');
    $this->actingAs($fakultas)->get(tautanMasuk($this->mahasiswa, $fakultas))->assertRedirect();
    expect(auth()->id())->toBe($fakultas->id);

    $this->actingAs($this->admin)->get(tautanMasuk($this->admin))->assertRedirect();
    expect(auth()->id())->toBe($this->admin->id);

    $nonaktif = User::factory()->create(['is_active' => false])->assignRole('mahasiswa');
    $this->get(tautanMasuk($nonaktif))->assertRedirect();
    expect(auth()->id())->toBe($this->admin->id);

    $this->get(tautanMasuk($this->mahasiswa));
    $lain = User::factory()->create()->assignRole('dosen');
    $this->get(tautanMasuk($lain, adminId: $this->admin->id));
    expect(auth()->id())->toBe($this->mahasiswa->id);
});

it('kembali ke akun super-admin saat mengakhiri sesi dan mencatatnya', function () {
    $this->actingAs($this->admin)->get(tautanMasuk($this->mahasiswa));

    $this->post(route('impersonasi.akhiri'))->assertRedirect(Filament::getPanel('admin')->getUrl());

    expect(auth()->id())->toBe($this->admin->id)
        ->and(session()->has('impersonator_id'))->toBeFalse()
        ->and(Activity::where('event', 'impersonasi_selesai')->where('causer_id', $this->admin->id)->exists())->toBeTrue();
});

it('menolak mengakhiri bila tidak sedang menyamar', function () {
    $this->actingAs($this->admin)->post(route('impersonasi.akhiri'))->assertNotFound();
});

it('keluar sepenuhnya bila akun asal tidak lagi sah saat mengakhiri', function () {
    $this->actingAs($this->admin)->get(tautanMasuk($this->mahasiswa));
    $this->admin->update(['is_active' => false]);

    $this->post(route('impersonasi.akhiri'))->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
});

it('membawa impersonator_id pada aktivitas selama menyamar', function () {
    $this->actingAs($this->admin)->get(tautanMasuk($this->mahasiswa));

    activity('uji')->causedBy(auth()->user())->log('tindakan saat menyamar');

    $log = Activity::where('log_name', 'uji')->firstOrFail();
    expect($log->properties['impersonator_id'])->toBe($this->admin->id);
});

it('menampilkan banner dan tidak memaksa ganti sandi target selama menyamar', function () {
    $this->mahasiswa->forceFill(['wajib_ganti_sandi' => true])->save();
    $this->actingAs($this->admin)->get(tautanMasuk($this->mahasiswa));

    $this->get(Filament::getPanel('portal')->getUrl())
        ->assertSuccessful()
        ->assertSee('Mode darurat')
        ->assertSee('Kembali ke akun saya');
});

it('tidak menampilkan banner pada sesi biasa', function () {
    $this->actingAs($this->admin)->get(Filament::getPanel('admin')->getUrl())->assertDontSee('Mode darurat');
});

it('melarang mengganti kata sandi target selama menyamar', function () {
    $this->actingAs($this->admin)->get(tautanMasuk($this->mahasiswa));

    expect(fn () => app(GantiSandi::class)->jalankan($this->mahasiswa, 'apa-saja', 'SandiBaruAman789'))->toThrow(ValidationException::class);
});

it('menampilkan aksi Masuk sebagai hanya bagi super-admin dan bukan untuk diri sendiri', function () {
    Filament::setCurrentPanel('admin');

    $this->actingAs($this->admin);
    Livewire::test(ListUsers::class)
        ->assertTableActionVisible('masukSebagai', $this->mahasiswa)
        ->assertTableActionHidden('masukSebagai', $this->admin);

    $this->actingAs(User::factory()->create()->assignRole('admin-fakultas'));
    Livewire::test(ListUsers::class)->assertTableActionHidden('masukSebagai', $this->mahasiswa);
});

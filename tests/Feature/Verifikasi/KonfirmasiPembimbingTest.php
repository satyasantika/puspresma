<?php

use App\Actions\Pembimbing\KonfirmasiPembimbingan;
use App\Actions\Pembimbing\SangkalPembimbingan;
use App\Actions\Prestasi\SimpanAnggotaTim;
use App\Actions\Prestasi\VerifikasiPrestasi;
use App\Enums\StatusKonfirmasiPembimbing;
use App\Events\PembimbingMenjawab;
use App\Filament\Portal\Resources\KonfirmasiPembimbingResource;
use App\Filament\Portal\Resources\KonfirmasiPembimbingResource\Pages\ListKonfirmasiPembimbing;
use App\Filament\Portal\Resources\PrestasiBimbinganResource\Pages\ListPrestasiBimbingan;
use App\Models\Dosen;
use App\Models\Prestasi;
use App\Models\TautanBerkas;
use App\Models\User;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class]);
    Http::fake(['*' => Http::response('', 200)]);
    Filament::setCurrentPanel('portal');
    $this->dosen = Dosen::factory()->create();
    $this->userDosen = User::factory()->create(['email' => $this->dosen->email])->assignRole('dosen');
    $this->dosen->update(['user_id' => $this->userDosen->id]);
    $this->prestasi = Prestasi::factory()->diajukan()->create([
        'dosen_pembimbing_id' => $this->dosen->id,
        'status_konfirmasi_pembimbing' => StatusKonfirmasiPembimbing::Menunggu,
    ]);
    TautanBerkas::factory()->untukPrestasi($this->prestasi)->sertifikat()->create();
});

it('mengizinkan dosen yang ditunjuk mengonfirmasi dan mengirim event', function () {
    Event::fake([PembimbingMenjawab::class]);

    app(KonfirmasiPembimbingan::class)->jalankan($this->prestasi, $this->userDosen);

    $p = $this->prestasi->refresh();
    expect($p->status_konfirmasi_pembimbing)->toBe(StatusKonfirmasiPembimbing::Dikonfirmasi)->and($p->dikonfirmasi_pembimbing_at)->not->toBeNull();
    Event::assertDispatched(PembimbingMenjawab::class, fn ($e) => $e->dikonfirmasi === true);
});

it('menolak dosen lain, pengaju, dan jawaban kedua', function () {
    $lain = User::factory()->create()->assignRole('dosen');
    Dosen::factory()->create(['user_id' => $lain->id]);

    expect(fn () => app(KonfirmasiPembimbingan::class)->jalankan($this->prestasi, $lain))->toThrow(AuthorizationException::class);
    expect(fn () => app(KonfirmasiPembimbingan::class)->jalankan($this->prestasi, $this->prestasi->pembuat))->toThrow(AuthorizationException::class);

    app(KonfirmasiPembimbingan::class)->jalankan($this->prestasi, $this->userDosen);
    expect(fn () => app(KonfirmasiPembimbingan::class)->jalankan($this->prestasi->refresh(), $this->userDosen))->toThrow(AuthorizationException::class);
});

it('mewajibkan alasan minimal 10 karakter saat menyangkal', function () {
    expect(fn () => app(SangkalPembimbingan::class)->jalankan($this->prestasi, $this->userDosen, ''))->toThrow(ValidationException::class);
    expect(fn () => app(SangkalPembimbingan::class)->jalankan($this->prestasi, $this->userDosen, 'singkat'))->toThrow(ValidationException::class);

    app(SangkalPembimbingan::class)->jalankan($this->prestasi, $this->userDosen, 'Saya tidak membimbing kegiatan ini.');

    expect($this->prestasi->refresh()->status_konfirmasi_pembimbing)->toBe(StatusKonfirmasiPembimbing::Disangkal)
        ->and($this->prestasi->catatan_pembimbing)->toBe('Saya tidak membimbing kegiatan ini.');
});

it('mereset konfirmasi saat pembimbing diganti atau dihapus oleh pengaju', function () {
    $this->prestasi->forceFill(['status' => 'perlu_perbaikan'])->save();
    app(KonfirmasiPembimbingan::class)->jalankan($this->prestasi, $this->userDosen);
    $baru = Dosen::factory()->create();
    $pengaju = $this->prestasi->pembuat;

    app(SimpanAnggotaTim::class)->jalankan($this->prestasi->refresh(), [], [], ['dosen_pembimbing_id' => $baru->id], User::factory()->create()->assignRole('admin-fakultas'));
    $p = $this->prestasi->refresh();
    expect($p->status_konfirmasi_pembimbing)->toBe(StatusKonfirmasiPembimbing::TidakAda)->and($p->dikonfirmasi_pembimbing_at)->toBeNull();

    $p->forceFill(['status_konfirmasi_pembimbing' => StatusKonfirmasiPembimbing::Disangkal])->save();
    app(SimpanAnggotaTim::class)->jalankan($p->refresh(), [], [], [], User::factory()->create()->assignRole('admin-fakultas'));
    expect($p->refresh()->dosen_pembimbing_id)->toBeNull()->and($p->status_konfirmasi_pembimbing)->toBe(StatusKonfirmasiPembimbing::TidakAda);
});

it('memblokir verifikasi saat disangkal dan mengizinkannya setelah dikonfirmasi', function () {
    $fakultas = User::factory()->create()->assignRole('admin-fakultas');
    app(SangkalPembimbingan::class)->jalankan($this->prestasi, $this->userDosen, 'Saya tidak membimbing kegiatan ini.');
    expect(fn () => app(VerifikasiPrestasi::class)->jalankan($this->prestasi->refresh(), $fakultas, 'Catatan yang cukup panjang.', false, true))->toThrow(ValidationException::class, 'menyangkal');

    $this->prestasi->forceFill(['status_konfirmasi_pembimbing' => StatusKonfirmasiPembimbing::Menunggu])->save();
    app(KonfirmasiPembimbingan::class)->jalankan($this->prestasi->refresh(), $this->userDosen);
    app(VerifikasiPrestasi::class)->jalankan($this->prestasi->refresh(), $fakultas);

    expect($this->prestasi->refresh()->status->value)->toBe('terverifikasi');
});

it('menampilkan daftar permintaan dan badge navigasi bagi dosen serta aksi konfirmasi', function () {
    Prestasi::factory()->diajukan()->create();
    $this->actingAs($this->userDosen);

    expect(KonfirmasiPembimbingResource::getNavigationBadge())->toBe('1');
    Livewire::test(ListKonfirmasiPembimbing::class)
        ->assertCanSeeTableRecords([$this->prestasi])
        ->callAction(TestAction::make('konfirmasi')->table($this->prestasi))
        ->assertNotified('Pembimbingan dikonfirmasi');

    expect(KonfirmasiPembimbingResource::getNavigationBadge())->toBeNull();
    Livewire::test(ListPrestasiBimbingan::class)->assertCanSeeTableRecords([$this->prestasi]);
});

it('menyangkal lewat modal dan menolak alasan kosong', function () {
    $this->actingAs($this->userDosen);

    Livewire::test(ListKonfirmasiPembimbing::class)
        ->callAction(TestAction::make('sangkal')->table($this->prestasi), ['alasan' => ''])->assertHasActionErrors(['alasan' => 'required']);
    Livewire::test(ListKonfirmasiPembimbing::class)
        ->callAction(TestAction::make('sangkal')->table($this->prestasi), ['alasan' => 'Bukan bimbingan saya.'])->assertNotified('Pembimbingan disangkal');

    expect($this->prestasi->refresh()->status_konfirmasi_pembimbing)->toBe(StatusKonfirmasiPembimbing::Disangkal);
});

it('menolak mahasiswa dan dosen lain pada halaman konfirmasi dan tidak memuat kontak mahasiswa', function () {
    $mahasiswa = User::factory()->create()->assignRole('mahasiswa');
    $this->actingAs($mahasiswa)->get(KonfirmasiPembimbingResource::getUrl('index'))->assertForbidden();

    $lain = User::factory()->create()->assignRole('dosen');
    Dosen::factory()->create(['user_id' => $lain->id]);
    $this->actingAs($lain);
    Livewire::test(ListKonfirmasiPembimbing::class)->assertCanNotSeeTableRecords([$this->prestasi]);

    $this->actingAs($this->userDosen)->get(KonfirmasiPembimbingResource::getUrl('index'))
        ->assertOk()->assertDontSee($this->prestasi->pengaju->email)->assertDontSee($this->prestasi->pengaju->nomor_hp);
});

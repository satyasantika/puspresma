<?php

use App\Actions\Prestasi\AjukanPrestasi;
use App\Actions\Prestasi\TarikKembaliPrestasi;
use App\Actions\Prestasi\TransisiStatus;
use App\Enums\StatusKonfirmasiPembimbing;
use App\Enums\StatusPrestasi;
use App\Events\StatusPrestasiBerubah;
use App\Exceptions\PrestasiTidakLengkap;
use App\Exceptions\TransisiTidakSah;
use App\Filament\Portal\Resources\PrestasiSayaResource;
use App\Filament\Portal\Resources\PrestasiSayaResource\Pages\ListPrestasiSaya;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\RiwayatStatusPrestasi;
use App\Models\TautanBerkas;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    Http::fake(['*' => Http::response('', 200)]);
    Filament::setCurrentPanel('portal');
    $this->pengaju = Mahasiswa::factory()->create();
    $this->user = User::factory()->create(['email' => $this->pengaju->email])->assignRole('mahasiswa');
    $this->pengaju->update(['user_id' => $this->user->id]);
    $this->lengkap = function (array $atribut = []): Prestasi {
        $p = Prestasi::factory()->for($this->pengaju, 'pengaju')->create($atribut);
        TautanBerkas::factory()->untukPrestasi($p)->sertifikat()->create();

        return $p;
    };
});

it('menolak ajukan tanpa tautan sertifikat dengan pesan kekurangan', function () {
    $p = Prestasi::factory()->for($this->pengaju, 'pengaju')->create();

    try {
        app(AjukanPrestasi::class)->jalankan($p, $this->user);
        $this->fail('seharusnya tidak lengkap');
    } catch (PrestasiTidakLengkap $e) {
        expect($e->pesan)->toContain('Minimal satu tautan sertifikat/piagam wajib dilampirkan.');
    }
    expect($p->refresh()->status)->toBe(StatusPrestasi::Draf);
});

it('mengajukan prestasi lengkap dengan nomor berurutan dan riwayat status', function () {
    $tahun = now()->year;
    $a = ($this->lengkap)();
    $b = ($this->lengkap)();

    Event::fake([StatusPrestasiBerubah::class]);
    app(AjukanPrestasi::class)->jalankan($a, $this->user);
    app(AjukanPrestasi::class)->jalankan($b, $this->user);

    expect($a->refresh()->status)->toBe(StatusPrestasi::Diajukan)
        ->and($a->nomor_registrasi)->toBe(sprintf('PRS-%d-00001', $tahun))
        ->and($b->refresh()->nomor_registrasi)->toBe(sprintf('PRS-%d-00002', $tahun))
        ->and($a->diajukan_at)->not->toBeNull();
    $riwayat = RiwayatStatusPrestasi::where('prestasi_id', $a->id)->orderBy('created_at')->get();
    expect($riwayat->last()->dari_status)->toBe(StatusPrestasi::Draf)->and($riwayat->last()->ke_status)->toBe(StatusPrestasi::Diajukan);
    Event::assertDispatched(StatusPrestasiBerubah::class, 2);
});

it('tidak menghasilkan nomor ganda untuk dua pengajuan berurutan cepat dan melanjutkan dari nomor terbesar', function () {
    $tahun = now()->year;
    Prestasi::factory()->diajukan()->create(['nomor_registrasi' => sprintf('PRS-%d-00041', $tahun)]);
    $a = ($this->lengkap)();
    $b = ($this->lengkap)();

    app(AjukanPrestasi::class)->jalankan($a, $this->user);
    app(AjukanPrestasi::class)->jalankan($b, $this->user);

    expect([$a->refresh()->nomor_registrasi, $b->refresh()->nomor_registrasi])->toBe([sprintf('PRS-%d-00042', $tahun), sprintf('PRS-%d-00043', $tahun)]);
});

it('mempertahankan nomor saat ajukan ulang dari perlu_perbaikan', function () {
    $p = ($this->lengkap)();
    app(AjukanPrestasi::class)->jalankan($p, $this->user);
    $nomor = $p->refresh()->nomor_registrasi;
    $admin = User::factory()->create()->assignRole('admin-fakultas');
    app(TransisiStatus::class)->ubah($p, StatusPrestasi::PerluPerbaikan, $admin, 'Mohon perbaiki tautan sertifikat.');

    app(AjukanPrestasi::class)->jalankan($p->refresh(), $this->user);

    expect($p->refresh()->nomor_registrasi)->toBe($nomor)->and($p->status)->toBe(StatusPrestasi::Diajukan);
});

it('menolak anggota non-pengaju mengajukan', function () {
    $p = ($this->lengkap)();
    $lain = Mahasiswa::factory()->create();
    $userLain = User::factory()->create(['email' => $lain->email])->assignRole('mahasiswa');
    $lain->update(['user_id' => $userLain->id]);

    expect(fn () => app(AjukanPrestasi::class)->jalankan($p, $userLain))->toThrow(AuthorizationException::class);
});

it('melempar TransisiTidakSah untuk draf ke terverifikasi dan mewajibkan catatan 10 karakter', function () {
    $p = ($this->lengkap)();
    $admin = User::factory()->create()->assignRole('admin-fakultas');
    $transisi = app(TransisiStatus::class);

    expect(fn () => $transisi->ubah($p, StatusPrestasi::Terverifikasi, $admin))->toThrow(TransisiTidakSah::class);

    $p->forceFill(['status' => 'diajukan'])->save();
    expect(fn () => $transisi->ubah($p->refresh(), StatusPrestasi::PerluPerbaikan, $admin, 'pendek'))->toThrow(ValidationException::class);
    expect(fn () => $transisi->ubah($p->refresh(), StatusPrestasi::Ditolak, $admin, null))->toThrow(ValidationException::class);
});

it('membaca ulang status terkini dengan lock sehingga transisi basi ditolak', function () {
    $p = ($this->lengkap)();
    $basi = Prestasi::find($p->id);
    app(AjukanPrestasi::class)->jalankan($p, $this->user);

    // Instance basi masih berstatus draf, tetapi di basis data sudah diajukan.
    expect(fn () => app(AjukanPrestasi::class)->jalankan($basi, $this->user))->toThrow(TransisiTidakSah::class);
    expect(Prestasi::find($p->id)->status)->toBe(StatusPrestasi::Diajukan);
});

it('mengatur status konfirmasi pembimbing menjadi menunggu saat ada dosen', function () {
    $dosen = Dosen::factory()->create();
    $p = ($this->lengkap)(['dosen_pembimbing_id' => $dosen->id]);

    app(AjukanPrestasi::class)->jalankan($p, $this->user);

    expect($p->refresh()->status_konfirmasi_pembimbing)->toBe(StatusKonfirmasiPembimbing::Menunggu);

    app(TarikKembaliPrestasi::class)->jalankan($p, $this->user);
    expect($p->refresh()->status_konfirmasi_pembimbing)->toBe(StatusKonfirmasiPembimbing::TidakAda);
});

it('menarik kembali hanya oleh pengaju dan hanya dari diajukan', function () {
    $p = ($this->lengkap)();
    expect(fn () => app(TarikKembaliPrestasi::class)->jalankan($p, $this->user))->toThrow(AuthorizationException::class);

    app(AjukanPrestasi::class)->jalankan($p, $this->user);
    $admin = User::factory()->create()->assignRole('admin-fakultas');
    expect(fn () => app(TarikKembaliPrestasi::class)->jalankan($p->refresh(), $admin))->toThrow(AuthorizationException::class);

    app(TarikKembaliPrestasi::class)->jalankan($p->refresh(), $this->user);
    expect($p->refresh()->status)->toBe(StatusPrestasi::Draf)->and($p->nomor_registrasi)->not->toBeNull();
});

it('menampilkan aksi Ajukan, kekurangan di modal, dan riwayat di halaman lihat', function () {
    $kurang = Prestasi::factory()->for($this->pengaju, 'pengaju')->create();
    $lengkap = ($this->lengkap)();
    $this->actingAs($this->user);

    Livewire::test(ListPrestasiSaya::class)
        ->mountAction(TestAction::make('ajukan')->table($kurang))
        ->assertMountedActionModalSee('Minimal satu tautan sertifikat/piagam')
        ->callMountedAction()
        ->assertNotified('Prestasi belum lengkap');

    Livewire::test(ListPrestasiSaya::class)->callAction(TestAction::make('ajukan')->table($lengkap))->assertNotified('Prestasi diajukan');

    expect($lengkap->refresh()->status)->toBe(StatusPrestasi::Diajukan);
    $this->get(PrestasiSayaResource::getUrl('view', ['record' => $lengkap]))->assertOk()->assertSee('Riwayat status')->assertSee('Diajukan');
    Livewire::test(ListPrestasiSaya::class)
        ->assertActionHidden(TestAction::make('ajukan')->table($lengkap))
        ->assertActionVisible(TestAction::make('tarikKembali')->table($lengkap));
});

it('tidak mengubah status di luar app/Actions/Prestasi', function () {
    $pelanggar = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path(), FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $berkas) {
        $path = $berkas->getPathname();
        // Models/Prestasi.php hanya memuat nilai bawaan atribut ($attributes) saat record dibuat.
        if (! str_ends_with($path, '.php') || str_contains($path, '/app/Actions/Prestasi/') || str_ends_with($path, '/Models/Prestasi.php')) {
            continue;
        }
        if (preg_match('/\$\w+->status\s*=[^=]|[\'"]status[\'"]\s*=>\s*(StatusPrestasi|[\'"](draf|diajukan|lolos_prodi|perlu_perbaikan|terverifikasi|ditolak))/', file_get_contents($path))) {
            $pelanggar[] = str_replace(app_path().'/', '', $path);
        }
    }

    expect($pelanggar)->toBe([]);
});

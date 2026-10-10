<?php

use App\Actions\Surat\CabutSuratKeteranganPrestasi;
use App\Actions\Surat\TerbitkanSuratKeteranganPrestasi;
use App\Enums\StatusSurat;
use App\Filament\Admin\Resources\SuratKeteranganPrestasiResource;
use App\Filament\Admin\Resources\SuratKeteranganPrestasiResource\Pages\CreateSuratKeteranganPrestasi;
use App\Filament\Admin\Resources\SuratKeteranganPrestasiResource\Pages\ListSuratKeteranganPrestasi;
use App\Filament\Portal\Resources\SuratSayaResource\Pages\ListSuratSaya;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\SuratKeteranganPrestasi;
use App\Models\User;
use App\Services\CetakSuratKeteranganPrestasi;
use App\Services\Pengaturan;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class]);
    RateLimiter::clear('verifikasi-surat');
    $this->fakultas = User::factory()->create()->assignRole('admin-fakultas');
    $this->fakultas->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();
    $this->mhs = Mahasiswa::factory()->create(['nama' => 'Siti Aminah', 'npm' => '2100000555']);
    $this->userMhs = User::factory()->create(['email' => $this->mhs->email])->assignRole('mahasiswa');
    $this->mhs->update(['user_id' => $this->userMhs->id]);
    $this->p1 = Prestasi::factory()->terverifikasi()->for($this->mhs, 'pengaju')->create(['nama_kegiatan' => 'Lomba Debat Nasional']);
    $this->p2 = Prestasi::factory()->terverifikasi()->for($this->mhs, 'pengaju')->create(['nama_kegiatan' => 'Olimpiade Matematika']);
    $this->data = ['keperluan' => 'Beasiswa', 'penandatangan_nama' => 'Dr. Dekan', 'penandatangan_jabatan' => 'Dekan FKIP', 'penandatangan_nip' => '19700101'];
    $this->terbit = fn (?array $ids = null, ?User $oleh = null) => app(TerbitkanSuratKeteranganPrestasi::class)->jalankan($this->mhs, $ids ?? [$this->p1->id, $this->p2->id], $this->data, $oleh ?? $this->fakultas);
});

it('menerbitkan surat dengan nomor berformat dan berurutan serta hanya menyimpan data', function () {
    $a = ($this->terbit)();
    $b = ($this->terbit)([$this->p1->id]);

    expect($a->nomor)->toBe('001/PRS/FKIP/'.now()->year)->and($b->nomor)->toBe('002/PRS/FKIP/'.now()->year)
        ->and($a->prestasi)->toHaveCount(2)->and($a->status)->toBe(StatusSurat::Aktif)->and($a->diterbitkan_oleh)->toBe($this->fakultas->id);

    Pengaturan::setel('format_nomor_surat_prestasi', 'SK/{tahun}/{urut}');
    expect(($this->terbit)([$this->p2->id])->nomor)->toBe('SK/'.now()->year.'/003');
});

it('menolak prestasi belum terverifikasi, milik orang lain, atau pilihan kosong, dan pelaku tanpa izin', function () {
    $draf = Prestasi::factory()->for($this->mhs, 'pengaju')->create();
    $milikLain = Prestasi::factory()->terverifikasi()->create();

    expect(fn () => ($this->terbit)([$draf->id]))->toThrow(ValidationException::class);
    expect(fn () => ($this->terbit)([$milikLain->id]))->toThrow(ValidationException::class);
    expect(fn () => ($this->terbit)([]))->toThrow(ValidationException::class);
    expect(fn () => ($this->terbit)(null, User::factory()->create()->assignRole('admin-prodi')))->toThrow(AuthorizationException::class);
    expect(SuratKeteranganPrestasi::count())->toBe(0);
});

it('mencetak PDF application/pdf tanpa menulis berkas dan QR berisi url verifikasi', function () {
    $surat = ($this->terbit)();
    $sebelum = [Storage::disk('local')->allFiles(), Storage::disk('public')->allFiles(), Storage::disk('tmp')->allFiles()];

    $respons = app(CetakSuratKeteranganPrestasi::class)->stream($surat);
    ob_start();
    $respons->sendContent();
    $isi = ob_get_clean();

    expect($respons->headers->get('Content-Type'))->toContain('application/pdf')->and(substr($isi, 0, 4))->toBe('%PDF')
        ->and([Storage::disk('local')->allFiles(), Storage::disk('public')->allFiles(), Storage::disk('tmp')->allFiles()])->toBe($sebelum);
    expect(app(CetakSuratKeteranganPrestasi::class)->qr($surat))->toStartWith('data:image/png;base64,')
        ->and(route('verifikasi.surat', $surat->id))->toContain($surat->id);
});

it('menampilkan halaman verifikasi publik tanpa npm atau url drive, dan status dicabut seketika', function () {
    $surat = ($this->terbit)();
    $this->p1->update(['url_kegiatan' => 'https://kegiatan.example.org/x']);

    $this->get(route('verifikasi.surat', $surat->id))->assertOk()
        ->assertSee($surat->nomor)->assertSee('Siti Aminah')->assertSee('Lomba Debat Nasional')->assertSee('Status: Aktif')
        ->assertDontSee('2100000555')->assertDontSee('drive.google.com')->assertDontSee($this->mhs->email);

    app(CabutSuratKeteranganPrestasi::class)->jalankan($surat, $this->fakultas, 'Data keliru, diterbitkan ulang.');

    $this->get(route('verifikasi.surat', $surat->id))->assertOk()->assertSee('Status: Dicabut');
    expect($surat->refresh()->status)->toBe(StatusSurat::Dicabut);
});

it('mewajibkan alasan pencabutan, menolak pencabut tanpa izin, dan menampilkan 404 untuk id tak dikenal', function () {
    $surat = ($this->terbit)();

    expect(fn () => app(CabutSuratKeteranganPrestasi::class)->jalankan($surat, $this->fakultas, ' '))->toThrow(ValidationException::class);
    expect(fn () => app(CabutSuratKeteranganPrestasi::class)->jalankan($surat, $this->userMhs, 'Mencoba mencabut sendiri.'))->toThrow(AuthorizationException::class);
    $this->get(route('verifikasi.surat', '00000000-0000-7000-8000-000000000000'))->assertNotFound();
    $this->get('/verifikasi/bukan-uuid')->assertNotFound();
});

it('mencetak PDF surat dicabut dengan watermark dan membatasi verifikasi 30 permintaan per menit', function () {
    $surat = ($this->terbit)();
    app(CabutSuratKeteranganPrestasi::class)->jalankan($surat, $this->fakultas, 'Dicabut untuk uji watermark.');

    expect(substr(app(CetakSuratKeteranganPrestasi::class)->pdf($surat->refresh()), 0, 4))->toBe('%PDF');

    foreach (range(1, 30) as $_) {
        $this->get(route('verifikasi.surat', $surat->id))->assertOk();
    }
    $this->get(route('verifikasi.surat', $surat->id))->assertStatus(429);
});

it('menerbitkan lewat halaman admin dan membatasi akses lihat/cetak menurut peran', function () {
    Filament::setCurrentPanel('admin');
    $this->actingAs($this->fakultas);

    Livewire::test(CreateSuratKeteranganPrestasi::class)
        ->fillForm(['mahasiswa_id' => $this->mhs->id, 'prestasi_ids' => [$this->p1->id], 'keperluan' => 'SKPI', 'penandatangan_nama' => 'Dr. Dekan', 'penandatangan_jabatan' => 'Dekan'])
        ->call('create')->assertHasNoFormErrors();
    $surat = SuratKeteranganPrestasi::firstWhere('keperluan', 'SKPI');
    expect($surat)->not->toBeNull();

    Livewire::test(ListSuratKeteranganPrestasi::class)->assertCanSeeTableRecords([$surat])->callAction(TestAction::make('cetak')->table($surat))->assertFileDownloaded();
    Livewire::test(ListSuratKeteranganPrestasi::class)->callAction(TestAction::make('cabut')->table($surat), ['alasan' => 'Salah penerima surat.']);
    expect($surat->refresh()->status)->toBe(StatusSurat::Dicabut);

    $pimpinan = User::factory()->create()->assignRole('pimpinan');
    $this->actingAs($pimpinan);
    Livewire::test(ListSuratKeteranganPrestasi::class)->assertCanSeeTableRecords([$surat])->assertActionHidden(TestAction::make('cabut')->table($surat));
    $this->get(SuratKeteranganPrestasiResource::getUrl('create'))->assertForbidden();
});

it('membiarkan mahasiswa melihat dan mencetak surat miliknya saja di portal', function () {
    Filament::setCurrentPanel('portal');
    $milik = ($this->terbit)();
    $lain = Mahasiswa::factory()->create();
    $punyaLain = app(TerbitkanSuratKeteranganPrestasi::class)->jalankan($lain, [Prestasi::factory()->terverifikasi()->for($lain, 'pengaju')->create()->id], $this->data, $this->fakultas);
    $this->actingAs($this->userMhs);

    Livewire::test(ListSuratSaya::class)->assertCanSeeTableRecords([$milik])->assertCanNotSeeTableRecords([$punyaLain])
        ->callAction(TestAction::make('cetak')->table($milik))->assertFileDownloaded();
});

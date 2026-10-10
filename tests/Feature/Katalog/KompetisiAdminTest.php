<?php

use App\Actions\Katalog\ArsipkanKompetisiKedaluwarsa;
use App\Actions\Katalog\DuplikatKompetisi;
use App\Actions\Katalog\SimpanKompetisi;
use App\Actions\Katalog\TerbitkanKompetisi;
use App\Enums\StatusKompetisi;
use App\Filament\Admin\Resources\KompetisiResource;
use App\Filament\Admin\Resources\KompetisiResource\Pages\CreateKompetisi;
use App\Filament\Admin\Resources\KompetisiResource\Pages\ListKompetisi;
use App\Models\Kompetisi;
use App\Models\Prodi;
use App\Models\User;
use Database\Factories\Concerns\MasterContoh;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    Http::fake(['*' => Http::response('', 200)]);
    Filament::setCurrentPanel('admin');
    $this->fakultas = User::factory()->create()->assignRole('admin-fakultas');
    $this->prodi = Prodi::factory()->create();
    $this->adminProdi = User::factory()->create(['prodi_id' => $this->prodi->id])->assignRole('admin-prodi');
    $this->data = fn (array $ubah = []): array => array_merge([
        'judul' => 'Kompetisi Debat Mahasiswa 2026',
        'penyelenggara' => 'Kemdiktisaintek',
        'jenis_kegiatan_id' => MasterContoh::jenis()->id,
        'tingkat_id' => MasterContoh::tingkat('nasional')->id,
        'tenggat_pendaftaran' => now()->addDays(10)->toDateTimeString(),
        'deskripsi' => '<p>Halo</p>',
        'biaya_pendaftaran' => '75000.50',
    ], $ubah);
});

it('membersihkan script dari deskripsi dan menyimpan biaya sebagai desimal', function () {
    $k = app(SimpanKompetisi::class)->jalankan(null, ($this->data)(['deskripsi' => '<p>Halo <script>alert(1)</script><a href="javascript:x()">x</a><a href="https://a.test">tautan</a><img src="x.png"></p>']), $this->fakultas);

    expect($k->deskripsi)->not->toContain('script')->not->toContain('javascript')->not->toContain('<img')
        ->and($k->deskripsi)->toContain('rel="nofollow noreferrer noopener"')->toContain('target="_blank"')
        ->and($k->fresh()->biaya_pendaftaran)->toBe('75000.50')
        ->and($k->slug)->toBe('kompetisi-debat-mahasiswa-2026');
});

it('membuat slug unik untuk judul sama dan menaikkan versi cache katalog', function () {
    Cache::forget('katalog:versi');
    $a = app(SimpanKompetisi::class)->jalankan(null, ($this->data)(), $this->fakultas);
    $b = app(SimpanKompetisi::class)->jalankan(null, ($this->data)(), $this->fakultas);

    expect($b->slug)->toBe($a->slug.'-2')->and((int) Cache::get('katalog:versi'))->toBeGreaterThanOrEqual(2);
});

it('menolak terbitkan saat tenggat lewat dan menerbitkan bila valid', function () {
    $lewat = Kompetisi::factory()->create(['tenggat_pendaftaran' => now()->subDay()]);
    $ok = Kompetisi::factory()->create();

    expect(fn () => app(TerbitkanKompetisi::class)->jalankan($lewat, $this->fakultas))->toThrow(ValidationException::class, 'Tenggat');

    app(TerbitkanKompetisi::class)->jalankan($ok, $this->fakultas);
    expect($ok->refresh()->status)->toBe(StatusKompetisi::Terbit)->and($ok->diterbitkan_at)->not->toBeNull()
        ->and(Kompetisi::masihDibuka()->count())->toBe(1);
});

it('membatasi admin-prodi: dipaksa menyasar prodinya dan hanya mengubah kompetisi sasaran prodinya', function () {
    $lain = Prodi::factory()->create();
    $baru = app(SimpanKompetisi::class)->jalankan(null, ($this->data)(['prodi_sasaran' => [$lain->id]]), $this->adminProdi);

    expect($baru->prodiSasaran->pluck('id')->all())->toBe([$this->prodi->id]);

    $milikFakultas = Kompetisi::factory()->create();
    $milikFakultas->prodiSasaran()->sync([$this->prodi->id, $lain->id]);
    expect(fn () => app(SimpanKompetisi::class)->jalankan($milikFakultas, ($this->data)(), $this->adminProdi))->toThrow(AuthorizationException::class);

    $semuaProdi = Kompetisi::factory()->create();
    expect(fn () => app(TerbitkanKompetisi::class)->jalankan($semuaProdi, $this->adminProdi))->toThrow(AuthorizationException::class);
});

it('mengarsipkan otomatis kompetisi terbit yang tenggatnya lewat', function () {
    $lewat = Kompetisi::factory()->terbit()->create(['tenggat_pendaftaran' => now()->subHour()]);
    $masih = Kompetisi::factory()->terbit()->create();
    $draf = Kompetisi::factory()->create(['tenggat_pendaftaran' => now()->subDay()]);

    expect(app(ArsipkanKompetisiKedaluwarsa::class)->jalankan())->toBe(1)
        ->and($lewat->refresh()->status)->toBe(StatusKompetisi::Diarsipkan)
        ->and($masih->refresh()->status)->toBe(StatusKompetisi::Terbit)
        ->and($draf->refresh()->status)->toBe(StatusKompetisi::Draf);
});

it('menyimpan tautan poster lewat PenyimpananBerkas dan menolak poster non-Drive', function () {
    $k = app(SimpanKompetisi::class)->jalankan(null, ($this->data)(['url_poster' => 'https://drive.google.com/file/d/POSTER1/view']), $this->fakultas);

    expect($k->poster->drive_file_id)->toBe('POSTER1')->and($k->poster->jenis->value)->toBe('poster');
    expect(fn () => app(SimpanKompetisi::class)->jalankan(null, ($this->data)(['url_poster' => 'https://example.com/p.png']), $this->fakultas))->toThrow(ValidationException::class);

    app(SimpanKompetisi::class)->jalankan($k, ($this->data)(['url_poster' => 'https://drive.google.com/file/d/POSTER2/view']), $this->fakultas);
    expect($k->poster()->first()->drive_file_id)->toBe('POSTER2');
});

it('menduplikat kompetisi sebagai draf dengan tanggal geser setahun dan sasaran sama', function () {
    $k = Kompetisi::factory()->terbit()->create();
    $k->prodiSasaran()->sync([$this->prodi->id]);

    $salinan = app(DuplikatKompetisi::class)->jalankan($k, $this->fakultas);

    expect($salinan->status)->toBe(StatusKompetisi::Draf)->and($salinan->slug)->not->toBe($k->slug)
        ->and($salinan->tenggat_pendaftaran->isSameDay($k->tenggat_pendaftaran->copy()->addYear()))->toBeTrue()
        ->and($salinan->prodiSasaran->pluck('id')->all())->toBe([$this->prodi->id]);
});

it('menjalankan aksi terbitkan dari tabel dan menampilkan galat sebagai notifikasi', function () {
    $this->fakultas->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();
    $this->actingAs($this->fakultas);
    $ok = Kompetisi::factory()->create();
    $lewat = Kompetisi::factory()->create(['tenggat_pendaftaran' => now()->subDay()]);

    Livewire::test(ListKompetisi::class)
        ->callAction(TestAction::make('terbitkan')->table($ok))->assertNotified('Kompetisi diterbitkan')
        ->callAction(TestAction::make('terbitkan')->table($lewat))->assertNotified('Tidak dapat diproses');
    $this->get(KompetisiResource::getUrl('index'))->assertOk();
});

it('membuat kompetisi lewat halaman buat dan menolak pimpinan membuat', function () {
    $this->fakultas->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();
    $this->actingAs($this->fakultas);
    Livewire::test(CreateKompetisi::class)->fillForm(($this->data)(['judul' => 'Lomba Melalui Halaman']))->call('create')->assertHasNoFormErrors();
    expect(Kompetisi::firstWhere('judul', 'Lomba Melalui Halaman'))->not->toBeNull();

    $this->actingAs(User::factory()->create()->assignRole('pimpinan'))->get(KompetisiResource::getUrl('create'))->assertForbidden();
});

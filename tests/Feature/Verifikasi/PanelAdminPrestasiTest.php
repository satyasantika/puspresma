<?php

use App\Enums\StatusPrestasi;
use App\Filament\Admin\Resources\KandidatDuplikatResource;
use App\Filament\Admin\Resources\KandidatDuplikatResource\Pages\ListKandidatDuplikat;
use App\Filament\Admin\Resources\PrestasiResource;
use App\Filament\Admin\Resources\PrestasiResource\Pages\CreatePrestasi;
use App\Filament\Admin\Resources\PrestasiResource\Pages\ListPrestasi;
use App\Filament\Admin\Resources\PrestasiResource\Pages\ViewPrestasi;
use App\Filament\Admin\Resources\PrestasiResource\RelationManagers\SnapshotTautanRelationManager;
use App\Models\Aktivitas;
use App\Models\Capaian;
use App\Models\JenisKegiatan;
use App\Models\KandidatDuplikat;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\Prodi;
use App\Models\SnapshotTautanVerifikasi;
use App\Models\TautanBerkas;
use App\Models\Tingkat;
use App\Models\User;
use App\Services\Pengaturan;
use Database\Seeders\CapaianSeeder;
use Database\Seeders\JenisKegiatanSeeder;
use Database\Seeders\KategoriLombaSeeder;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Database\Seeders\TingkatSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class, JenisKegiatanSeeder::class, KategoriLombaSeeder::class, TingkatSeeder::class, CapaianSeeder::class]);
    Http::fake(['*' => Http::response('', 200)]);
    Filament::setCurrentPanel('admin');
    $this->prodiA = Prodi::factory()->create();
    $this->prodiB = Prodi::factory()->create();
    $this->prestasiA = Prestasi::factory()->diajukan()->for(Mahasiswa::factory()->for($this->prodiA), 'pengaju')->create();
    TautanBerkas::factory()->untukPrestasi($this->prestasiA)->sertifikat()->create();
    $this->prestasiB = Prestasi::factory()->diajukan()->for(Mahasiswa::factory()->for($this->prodiB), 'pengaju')->create();
    $this->admin = function (string $peran, ?Prodi $prodi = null): User {
        $u = User::factory()->create(['prodi_id' => $prodi?->id])->assignRole($peran);
        $u->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();

        return $u;
    };
});

it('membatasi admin-prodi pada prestasi yang melibatkan prodinya (tabel dan URL)', function () {
    $this->actingAs(($this->admin)('admin-prodi', $this->prodiA));

    Livewire::test(ListPrestasi::class)->set('activeTab', 'semua')
        ->assertCanSeeTableRecords([$this->prestasiA])
        ->assertCanNotSeeTableRecords([$this->prestasiB]);
    $this->get(PrestasiResource::getUrl('view', ['record' => $this->prestasiB]))->assertForbidden();
});

it('menampilkan antrean dengan badge dan semua prestasi untuk admin-fakultas', function () {
    $this->actingAs(($this->admin)('admin-fakultas'));

    Livewire::test(ListPrestasi::class)
        ->assertCanSeeTableRecords([$this->prestasiA, $this->prestasiB])
        ->set('activeTab', 'terverifikasi')->assertCanNotSeeTableRecords([$this->prestasiA]);
});

it('menyembunyikan aksi verifikasi dari admin-prodi dan pimpinan, dan mengizinkan lihat pimpinan', function () {
    $this->actingAs(($this->admin)('admin-prodi', $this->prodiA));
    Livewire::test(ViewPrestasi::class, ['record' => $this->prestasiA->id])
        ->assertActionHidden('verifikasi')->assertActionHidden('tolak')->assertActionHidden('bukaVerifikasi');

    $this->actingAs(($this->admin)('pimpinan'));
    Livewire::test(ViewPrestasi::class, ['record' => $this->prestasiA->id])
        ->assertSuccessful()
        ->assertActionHidden('verifikasi')->assertActionHidden('kembalikan')->assertActionHidden('tolak')->assertActionHidden('gantiTautan');
});

it('memverifikasi lewat aksi dan menyimpan snapshot yang tampil di relation manager', function () {
    $this->actingAs(($this->admin)('admin-fakultas'));

    Livewire::test(ViewPrestasi::class, ['record' => $this->prestasiA->id])
        ->callAction('verifikasi', ['sudah_membuka_tautan' => true])
        ->assertNotified('Prestasi diverifikasi');

    expect($this->prestasiA->refresh()->status)->toBe(StatusPrestasi::Terverifikasi);
    Livewire::test(SnapshotTautanRelationManager::class, ['ownerRecord' => $this->prestasiA, 'pageClass' => ViewPrestasi::class])
        ->assertCanSeeTableRecords(SnapshotTautanVerifikasi::all());
});

it('menolak verifikasi tanpa centang membuka tautan dan kembalikan/ganti tautan tanpa catatan', function () {
    $this->actingAs(($this->admin)('admin-fakultas'));

    Livewire::test(ViewPrestasi::class, ['record' => $this->prestasiA->id])
        ->callAction('verifikasi', ['sudah_membuka_tautan' => false])->assertHasActionErrors(['sudah_membuka_tautan']);
    Livewire::test(ViewPrestasi::class, ['record' => $this->prestasiA->id])
        ->callAction('kembalikan', ['catatan' => ''])->assertHasActionErrors(['catatan' => 'required']);
    expect($this->prestasiA->refresh()->status)->toBe(StatusPrestasi::Diajukan);

    $this->prestasiA->forceFill(['status' => 'terverifikasi'])->save();
    Livewire::test(ViewPrestasi::class, ['record' => $this->prestasiA->id])
        ->callAction('gantiTautan', ['tautan_id' => $this->prestasiA->tautanBukti()->first()->id, 'url_baru' => 'https://drive.google.com/file/d/NEW/view', 'catatan' => ''])
        ->assertHasActionErrors(['catatan']);
});

it('mengganti tautan prestasi terverifikasi dengan catatan, snapshot penggantian, dan log url lama ke baru', function () {
    $this->prestasiA->forceFill(['status' => 'terverifikasi'])->save();
    $tautan = $this->prestasiA->tautanBukti()->first();
    $this->actingAs(($this->admin)('admin-fakultas'));

    Livewire::test(ViewPrestasi::class, ['record' => $this->prestasiA->id])
        ->callAction('gantiTautan', ['tautan_id' => $tautan->id, 'url_baru' => 'https://drive.google.com/file/d/NEW99/view', 'catatan' => 'Tautan lama terhapus pemiliknya.'])
        ->assertNotified('Tautan diganti dan snapshot baru disimpan');

    expect($tautan->refresh()->drive_file_id)->toBe('NEW99')
        ->and(SnapshotTautanVerifikasi::where('alasan', 'penggantian')->count())->toBe(1);
    $log = Aktivitas::where('log_name', 'tautan')->where('event', 'updated')->latest()->first();
    expect($log->attribute_changes['attributes']['url'])->toBe('https://drive.google.com/file/d/NEW99/view');
});

it('menjalankan tahap prodi oleh admin-prodi bila diaktifkan', function () {
    Pengaturan::setel('verifikasi_prodi_aktif', true);
    $this->actingAs(($this->admin)('admin-prodi', $this->prodiA));

    Livewire::test(ViewPrestasi::class, ['record' => $this->prestasiA->id])
        ->assertActionVisible('loloskanProdi')->assertActionHidden('verifikasi')
        ->callAction('loloskanProdi')->assertNotified('Prestasi diloloskan ke tahap fakultas');

    expect($this->prestasiA->refresh()->status)->toBe(StatusPrestasi::LolosProdi);
});

it('membuat prestasi atas nama mahasiswa sehingga pengaju menjadi anggota', function () {
    $mhs = Mahasiswa::factory()->for($this->prodiA)->create();
    $kompetisi = JenisKegiatan::firstWhere('kode', 'kompetisi');
    $this->actingAs(($this->admin)('admin-fakultas'));

    Livewire::test(CreatePrestasi::class)
        ->fillForm([
            'pengaju_id' => $mhs->id,
            'jenis_kegiatan_id' => $kompetisi->id,
            'bidang' => 'non_akademik',
            'nama_kegiatan' => 'Festival Seni Arsip 2024',
            'penyelenggara' => 'Dinas Kebudayaan',
            'tanggal_mulai' => '2024-05-01',
            'tanggal_selesai' => '2024-05-03',
            'moda' => 'luring',
            'lokasi' => 'Tasikmalaya',
            'tingkat_id' => Tingkat::firstWhere('kode', 'lokal')->id,
            'capaian_id' => Capaian::where('jenis_kegiatan_id', $kompetisi->id)->firstWhere('kode', 'juara-3')->id,
            'bentuk' => 'individu',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $prestasi = Prestasi::firstWhere('nama_kegiatan', 'Festival Seni Arsip 2024');
    expect($prestasi->pengaju_id)->toBe($mhs->id)
        ->and($prestasi->anggota->firstWhere('mahasiswa_id', $mhs->id)->is_pengaju)->toBeTrue()
        ->and($prestasi->status)->toBe(StatusPrestasi::Draf);
});

it('menolak admin-prodi membuat prestasi atas nama dan menampilkan daftar kandidat duplikat global', function () {
    $this->actingAs(($this->admin)('admin-prodi', $this->prodiA));
    $this->get(PrestasiResource::getUrl('create'))->assertForbidden();

    $kandidat = KandidatDuplikat::create(['prestasi_id' => $this->prestasiA->id, 'pembanding_id' => $this->prestasiB->id, 'alasan' => 'tautan_sama', 'skor' => '100.00']);
    $this->actingAs(($this->admin)('admin-fakultas'));
    Livewire::test(ListKandidatDuplikat::class)->assertCanSeeTableRecords([$kandidat])
        ->callAction(TestAction::make('tinjau')->table($kandidat), ['status' => 'bukan_duplikat'])->assertNotified('Tinjauan disimpan');
    expect($kandidat->refresh()->status_tinjau->value)->toBe('bukan_duplikat');
    $this->get(KandidatDuplikatResource::getUrl('index'))->assertOk();
});

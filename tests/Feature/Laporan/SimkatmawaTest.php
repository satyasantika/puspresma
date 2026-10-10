<?php

use App\Actions\Simkatmawa\TandaiDilaporkanSimkatmawa;
use App\Filament\Admin\Pages\EksporSimkatmawa;
use App\Filament\Admin\Resources\PrestasiResource\Pages\ListPrestasi;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\Prodi;
use App\Models\SnapshotTautanVerifikasi;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Services\Ekspor\EksporService;
use App\Services\Ekspor\RegistriLaporan;
use Database\Factories\PrestasiAnggotaFactory;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\SimpleExcel\SimpleExcelReader;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class]);
    Filament::setCurrentPanel('admin');
    Storage::fake('tmp');
    $this->fakultas = User::factory()->create()->assignRole('admin-fakultas');
    $this->fakultas->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();
    $this->prodi = Prodi::factory()->create(['kode_pddikti' => '12345']);
    $this->prestasi = Prestasi::factory()->terverifikasi()->kelompok()->for(Mahasiswa::factory()->for($this->prodi), 'pengaju')->create(['nama_kegiatan' => 'Lomba SIMKATMAWA']);
    PrestasiAnggotaFactory::new()->create(['prestasi_id' => $this->prestasi->id]);
    $this->tautan = TautanBerkas::factory()->untukPrestasi($this->prestasi)->sertifikat()->create(['url' => 'https://drive.google.com/file/d/SERT1/view']);
    SnapshotTautanVerifikasi::create(['prestasi_id' => $this->prestasi->id, 'tautan_berkas_id' => $this->tautan->id, 'jenis' => 'sertifikat_piagam',
        'url' => 'https://drive.google.com/file/d/SERT1/view', 'status_cek' => 'dapat_diakses', 'user_id' => $this->fakultas->id]);
    $this->baca = function ($respons): array {
        ob_start();
        $respons->sendContent();
        $isi = ob_get_clean();
        $path = Storage::disk('local')->path('uji-simkatmawa-'.uniqid().'.csv');
        file_put_contents($path, $isi);
        $baris = SimpleExcelReader::create($path)->getRows()->toArray();
        unlink($path);

        return $baris;
    };
    $this->ekspor = fn (array $filter = []) => ($this->baca)(app(EksporService::class)->minta(RegistriLaporan::buat('simkatmawa'), 'csv', $filter, $this->fakultas));
});

it('mengikuti urutan dan header kolom dari config dan satu baris per anggota', function () {
    $baris = ($this->ekspor)();

    expect(array_keys($baris[0]))->toBe(array_keys(config('simkatmawa.kolom')))->and($baris)->toHaveCount(2)
        ->and($baris[0]['Kode PDDikti Prodi'])->toBe('12345')->and($baris[0]['Nama Kegiatan'])->toBe('Lomba SIMKATMAWA');
});

it('mengubah header dan urutan lewat config tanpa mengubah kode', function () {
    config(['simkatmawa.kolom' => ['Nama Lomba' => 'kegiatan.nama', 'NIM' => 'mahasiswa.npm']]);

    $baris = ($this->ekspor)();

    expect(array_keys($baris[0]))->toBe(['Nama Lomba', 'NIM']);
});

it('mendukung satu baris per prestasi lewat config', function () {
    config(['simkatmawa.satu_baris_per' => 'prestasi']);

    expect(($this->ekspor)())->toHaveCount(1);
});

it('mengambil url bukti dari snapshot walau tautan diubah setelah verifikasi', function () {
    $this->tautan->update(['url' => 'https://drive.google.com/file/d/BERUBAH/view']);

    $baris = ($this->ekspor)();

    expect($baris[0]['URL Sertifikat'])->toBe('https://drive.google.com/file/d/SERT1/view')->and($baris[0]['URL Foto'])->toBe('')
        ->and($baris[0]['Status Tautan'])->toBeString();
});

it('menggabungkan beberapa url sejenis dengan titik koma dan memakai snapshot terbaru per tautan', function () {
    $kedua = TautanBerkas::factory()->untukPrestasi($this->prestasi)->sertifikat()->create(['url' => 'https://drive.google.com/file/d/SERT2/view']);
    SnapshotTautanVerifikasi::create(['prestasi_id' => $this->prestasi->id, 'tautan_berkas_id' => $kedua->id, 'jenis' => 'sertifikat_piagam', 'url' => 'https://drive.google.com/file/d/SERT2/view', 'status_cek' => 'belum', 'user_id' => $this->fakultas->id]);

    expect(($this->ekspor)()[0]['URL Sertifikat'])->toBe('https://drive.google.com/file/d/SERT1/view ; https://drive.google.com/file/d/SERT2/view');
});

it('hanya mengekspor prestasi terverifikasi dan memberi catatan bila kode simkatmawa kosong', function () {
    Prestasi::factory()->diajukan()->create(['nama_kegiatan' => 'Belum Terverifikasi']);
    $this->prestasi->tingkat->update(['kode_simkatmawa' => 'TK-3']);

    $baris = ($this->ekspor)();

    expect(collect($baris)->pluck('Nama Kegiatan')->unique()->all())->toBe(['Lomba SIMKATMAWA'])
        ->and($baris[0]['Tingkat'])->toBe('TK-3')->and($baris[0]['Catatan Pemetaan'])->toContain('Kode SIMKATMAWA kosong')->toContain('jenis')->not->toContain('tingkat');
});

it('menyaring yang belum dilaporkan dan menandai prestasi sehingga tidak muncul lagi', function () {
    expect(($this->ekspor)())->toHaveCount(2);

    app(TandaiDilaporkanSimkatmawa::class)->jalankan([$this->prestasi], $this->fakultas);

    expect($this->prestasi->refresh()->dilaporkan_simkatmawa_at)->not->toBeNull()->and($this->prestasi->dilaporkan_simkatmawa_oleh)->toBe($this->fakultas->id)
        ->and(($this->ekspor)())->toHaveCount(0)->and(($this->ekspor)(['belum_dilaporkan' => false]))->toHaveCount(2);
});

it('menolak penandaan prestasi yang belum terverifikasi, oleh admin-prodi, dan oleh super-admin', function () {
    $draf = Prestasi::factory()->create();

    expect(fn () => app(TandaiDilaporkanSimkatmawa::class)->jalankan([$draf], $this->fakultas))->toThrow(AuthorizationException::class);
    expect(fn () => app(TandaiDilaporkanSimkatmawa::class)->jalankan([$this->prestasi], User::factory()->create()->assignRole('admin-prodi')))->toThrow(AuthorizationException::class);
    expect(fn () => app(TandaiDilaporkanSimkatmawa::class)->jalankan([$this->prestasi], User::factory()->create()->assignRole('super-admin')))->toThrow(AuthorizationException::class);
});

it('membatalkan penandaan dengan catatan wajib', function () {
    app(TandaiDilaporkanSimkatmawa::class)->jalankan([$this->prestasi], $this->fakultas);

    expect(fn () => app(TandaiDilaporkanSimkatmawa::class)->batalkan($this->prestasi, $this->fakultas, 'pendek'))->toThrow(ValidationException::class);
    app(TandaiDilaporkanSimkatmawa::class)->batalkan($this->prestasi, $this->fakultas, 'Salah tandai, data perlu dikoreksi.');

    expect($this->prestasi->refresh()->dilaporkan_simkatmawa_at)->toBeNull();
});

it('tidak meninggalkan berkas di storage pada ekspor kecil dan menolak admin-prodi di halaman', function () {
    $sebelum = [Storage::disk('local')->allFiles(), Storage::disk('tmp')->allFiles()];

    ($this->ekspor)();

    expect([Storage::disk('local')->allFiles(), Storage::disk('tmp')->allFiles()])->toBe($sebelum);
    $this->actingAs(User::factory()->create(['prodi_id' => $this->prodi->id])->assignRole('admin-prodi'))->get(EksporSimkatmawa::getUrl())->assertForbidden();
});

it('menampilkan pratinjau dengan tombol buka berkas serta menjalankan ekspor dan tandai dari halaman', function () {
    $this->actingAs($this->fakultas);

    Livewire::test(EksporSimkatmawa::class)
        ->assertSee('Lomba SIMKATMAWA')->assertSee(route('tautan.buka', $this->tautan), false)->assertSee('Format kolom resmi SIMKATMAWA perlu diverifikasi')
        ->callAction('csv')->assertFileDownloaded()
        ->callAction('tandai')->assertNotified('1 prestasi ditandai dilaporkan');

    expect($this->prestasi->refresh()->dilaporkan_simkatmawa_at)->not->toBeNull();
});

it('menandai lewat aksi massal dan memfilter dilaporkan pada tabel prestasi', function () {
    $lain = Prestasi::factory()->terverifikasi()->create();
    $this->actingAs($this->fakultas);

    Livewire::test(ListPrestasi::class)->set('activeTab', 'semua')
        ->selectTableRecords([$this->prestasi->getKey()])
        ->callAction(TestAction::make('tandaiSimkatmawa')->table()->bulk())
        ->assertNotified('1 prestasi ditandai dilaporkan');

    Livewire::test(ListPrestasi::class)->set('activeTab', 'semua')
        ->filterTable('dilaporkan', 'belum')->assertCanSeeTableRecords([$lain])->assertCanNotSeeTableRecords([$this->prestasi])
        ->filterTable('dilaporkan', 'ya')->assertCanSeeTableRecords([$this->prestasi])->assertCanNotSeeTableRecords([$lain]);
});

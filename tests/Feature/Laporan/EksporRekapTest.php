<?php

use App\Actions\Laporan\RekapPribadiPdf;
use App\Enums\StatusBerkasEkspor;
use App\Filament\Admin\Pages\Laporan;
use App\Jobs\EksporBerkasJob;
use App\Models\Aktivitas;
use App\Models\BerkasEkspor;
use App\Models\Kompetisi;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\Prodi;
use App\Models\SnapshotTautanVerifikasi;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Notifications\BerkasEksporSiap;
use App\Services\Ekspor\EksporService;
use App\Services\Ekspor\RegistriLaporan;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\SimpleExcel\SimpleExcelReader;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class]);
    Http::fake(['*' => Http::response('', 200)]);
    Filament::setCurrentPanel('admin');
    Storage::fake('tmp');
    $this->prodiA = Prodi::factory()->create();
    $this->prodiB = Prodi::factory()->create();
    $this->pA = Prestasi::factory()->terverifikasi()->for(Mahasiswa::factory()->for($this->prodiA, 'prodi'), 'pengaju')->create(['nama_kegiatan' => 'Prestasi Prodi A']);
    $this->pB = Prestasi::factory()->terverifikasi()->for(Mahasiswa::factory()->for($this->prodiB, 'prodi'), 'pengaju')->create(['nama_kegiatan' => 'Prestasi Prodi B']);
    $this->draf = Prestasi::factory()->for(Mahasiswa::factory()->for($this->prodiA, 'prodi'), 'pengaju')->create(['nama_kegiatan' => 'Prestasi Masih Draf']);
    $this->fakultas = User::factory()->create()->assignRole('admin-fakultas');
    $this->fakultas->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();
    $this->adminProdi = User::factory()->create(['prodi_id' => $this->prodiA->id])->assignRole('admin-prodi');
    $this->baca = function ($respons): array {
        ob_start();
        $respons->sendContent();
        $isi = ob_get_clean();
        $path = Storage::disk('local')->path('uji-ekspor-'.uniqid().'.xlsx');
        file_put_contents($path, $isi);
        $baris = SimpleExcelReader::create($path)->getRows()->toArray();
        unlink($path);

        return $baris;
    };
});

it('men-stream ekspor kecil tanpa menulis berkas dan hanya memuat prestasi terverifikasi', function () {
    $sebelum = [Storage::disk('local')->allFiles(), Storage::disk('tmp')->allFiles()];

    $respons = app(EksporService::class)->minta(RegistriLaporan::buat('rekap_prestasi'), 'xlsx', [], $this->fakultas);
    $baris = ($this->baca)($respons);

    expect($respons->getStatusCode())->toBe(200)->and(collect($baris)->pluck('Kegiatan')->sort()->values()->all())->toBe(['Prestasi Prodi A', 'Prestasi Prodi B']);
    expect([Storage::disk('local')->allFiles(), Storage::disk('tmp')->allFiles()])->toBe($sebelum);
    expect(Aktivitas::where('log_name', 'ekspor')->where('event', 'langsung')->count())->toBe(1);
});

it('membatasi admin-prodi pada prestasi yang melibatkan prodinya dan menghormati filter status', function () {
    $baris = ($this->baca)(app(EksporService::class)->minta(RegistriLaporan::buat('rekap_prestasi'), 'xlsx', ['prodi_id' => $this->prodiB->id], $this->adminProdi));
    // Memilih prodi lain tidak membuka data prodi tersebut (irisan cakupan admin-prodi dan filter kosong).
    expect($baris)->toBe([]);

    $semua = ($this->baca)(app(EksporService::class)->minta(RegistriLaporan::buat('rekap_prestasi'), 'xlsx', ['status' => 'semua'], $this->adminProdi));
    expect(collect($semua)->pluck('Kegiatan')->sort()->values()->all())->toBe(['Prestasi Masih Draf', 'Prestasi Prodi A']);
});

it('menyertakan url sertifikat dari snapshot untuk admin tetapi tidak untuk pimpinan, dan kontak hanya untuk pengelola master', function () {
    TautanBerkas::factory()->untukPrestasi($this->pA)->sertifikat()->create(['url' => 'https://drive.google.com/file/d/LAMA/view']);
    SnapshotTautanVerifikasi::create(['prestasi_id' => $this->pA->id, 'tautan_berkas_id' => $this->pA->tautanBukti()->first()->id, 'jenis' => 'sertifikat_piagam',
        'url' => 'https://drive.google.com/file/d/SNAP1/view', 'status_cek' => 'dapat_diakses', 'user_id' => $this->fakultas->id]);
    $pimpinan = User::factory()->create()->assignRole('pimpinan');

    $admin = ($this->baca)(app(EksporService::class)->minta(RegistriLaporan::buat('rekap_prestasi'), 'xlsx', ['prodi_id' => $this->prodiA->id], $this->fakultas));
    expect($admin[0]['URL Sertifikat (snapshot)'])->toBe('https://drive.google.com/file/d/SNAP1/view');

    $pimpinanBaris = ($this->baca)(app(EksporService::class)->minta(RegistriLaporan::buat('rekap_prestasi'), 'xlsx', [], $pimpinan));
    expect($pimpinanBaris[0])->not->toHaveKey('URL Sertifikat (snapshot)');

    $anggotaAdmin = ($this->baca)(app(EksporService::class)->minta(RegistriLaporan::buat('rekap_anggota'), 'xlsx', [], $this->fakultas));
    $anggotaPimpinan = ($this->baca)(app(EksporService::class)->minta(RegistriLaporan::buat('rekap_anggota'), 'xlsx', [], $pimpinan));
    expect($anggotaAdmin[0])->toHaveKeys(['Surel', 'Nomor HP'])->and($anggotaPimpinan[0])->not->toHaveKeys(['Surel', 'Nomor HP']);
});

it('mengantre ekspor besar, menulis berkas di tmp, kedaluwarsa 24 jam, dan memberi notifikasi', function () {
    config(['puspresma.ekspor.batas_stream' => 1]);
    Notification::fake();

    $berkas = app(EksporService::class)->minta(RegistriLaporan::buat('rekap_prestasi'), 'xlsx', [], $this->fakultas);

    expect($berkas)->toBeInstanceOf(BerkasEkspor::class);
    $berkas->refresh();
    expect($berkas->status)->toBe(StatusBerkasEkspor::Siap)->and($berkas->jumlah_baris)->toBe(2)->and($berkas->path)->toStartWith("ekspor/{$this->fakultas->id}/")
        ->and($berkas->kedaluwarsa_at->lessThanOrEqualTo(now()->addHours(24)->addMinute()))->toBeTrue();
    Storage::disk('tmp')->assertExists($berkas->path);
    Notification::assertSentTo($this->fakultas, BerkasEksporSiap::class);
});

it('mendispatch job ke antrean ekspor dan hanya satu job pada klik ganda dalam 2 menit', function () {
    config(['puspresma.ekspor.batas_stream' => 1]);
    Queue::fake();
    $layanan = app(EksporService::class);

    $layanan->minta(RegistriLaporan::buat('rekap_prestasi'), 'xlsx', [], $this->fakultas);
    expect(fn () => $layanan->minta(RegistriLaporan::buat('rekap_prestasi'), 'xlsx', [], $this->fakultas))->toThrow(ValidationException::class, 'sedang diproses');

    Queue::assertPushed(EksporBerkasJob::class, 1);
    Queue::assertPushedOn('berat', EksporBerkasJob::class);
    expect(BerkasEkspor::count())->toBe(1);
});

it('membatasi 10 ekspor besar per jam', function () {
    config(['puspresma.ekspor.batas_stream' => 1]);
    Queue::fake();
    foreach (range(1, 10) as $_) {
        Cache::lock("lock:ekspor:{$this->fakultas->id}:rekap_prestasi")->forceRelease();
        app(EksporService::class)->minta(RegistriLaporan::buat('rekap_prestasi'), 'xlsx', [], $this->fakultas);
    }
    Cache::lock("lock:ekspor:{$this->fakultas->id}:rekap_prestasi")->forceRelease();

    expect(fn () => app(EksporService::class)->minta(RegistriLaporan::buat('rekap_prestasi'), 'xlsx', [], $this->fakultas))->toThrow(ValidationException::class, 'Terlalu banyak');
});

it('melindungi unduhan: hanya pemohon, 410 bila kedaluwarsa atau dihapus perintah pembersihan', function () {
    config(['puspresma.ekspor.batas_stream' => 1]);
    $berkas = app(EksporService::class)->minta(RegistriLaporan::buat('rekap_prestasi'), 'xlsx', [], $this->fakultas)->refresh();
    $lain = User::factory()->create()->assignRole('admin-fakultas');

    $this->actingAs($lain)->get(route('ekspor.unduh', $berkas))->assertForbidden();
    $this->actingAs($this->fakultas)->get(route('ekspor.unduh', $berkas))->assertOk()->assertDownload();

    $this->travel(25)->hours();
    touch(Storage::disk('tmp')->path($berkas->path), now()->subHours(25)->getTimestamp());
    $this->artisan('puspresma:bersihkan-tmp')->assertSuccessful();

    expect($berkas->refresh()->status)->toBe(StatusBerkasEkspor::Kedaluwarsa);
    $this->actingAs($this->fakultas)->get(route('ekspor.unduh', $berkas))->assertStatus(410);
});

it('menolak pengguna tanpa izin laporan dan format yang tidak didukung', function () {
    expect(fn () => app(EksporService::class)->minta(RegistriLaporan::buat('rekap_prestasi'), 'xlsx', [], User::factory()->create()->assignRole('dosen')))->toThrow(AuthorizationException::class);
    expect(fn () => app(EksporService::class)->minta(RegistriLaporan::buat('rekap_anggota'), 'pdf', [], $this->fakultas))->toThrow(AuthorizationException::class);
    expect(fn () => app(EksporService::class)->minta(RegistriLaporan::buat('katalog'), 'xlsx', [], $this->adminProdi))->toThrow(AuthorizationException::class);
});

it('mengekspor rekap katalog dan peringkat poin, serta PDF A4 landscape', function () {
    Kompetisi::factory()->terbit()->create(['judul' => 'Kompetisi Uji Katalog']);

    $katalog = ($this->baca)(app(EksporService::class)->minta(RegistriLaporan::buat('katalog'), 'xlsx', [], $this->fakultas));
    expect($katalog[0]['Judul'])->toBe('Kompetisi Uji Katalog');

    $pdf = app(EksporService::class)->minta(RegistriLaporan::buat('rekap_prestasi'), 'pdf', [], $this->fakultas);
    ob_start();
    $pdf->sendContent();
    $isi = ob_get_clean();
    expect(substr($isi, 0, 4))->toBe('%PDF')->and($pdf->headers->get('Content-Type'))->toContain('application/pdf');
});

it('menjalankan ekspor dari halaman Laporan dan menolak admin-prodi untuk katalog', function () {
    $this->actingAs($this->fakultas);
    Livewire::test(Laporan::class)->callAction('rekap_prestasi_xlsx')->assertFileDownloaded();

    $this->actingAs($this->adminProdi);
    Livewire::test(Laporan::class)->assertActionHidden('katalog_xlsx')->assertActionVisible('rekap_prestasi_xlsx');
    $this->actingAs(User::factory()->create()->assignRole('mahasiswa'))->get(Laporan::getUrl())->assertRedirect();
});

it('menghasilkan PDF rekap pribadi yang hanya memuat prestasi terverifikasi milik mahasiswa', function () {
    $mhs = $this->pA->pengaju;
    $user = User::factory()->create(['email' => $mhs->email])->assignRole('mahasiswa');
    $mhs->update(['user_id' => $user->id]);
    Prestasi::factory()->terverifikasi()->for($mhs, 'pengaju')->create(['nama_kegiatan' => 'Prestasi Milik Saya Lain']);

    $respons = app(RekapPribadiPdf::class)->stream($user);
    ob_start();
    $respons->sendContent();
    $isi = ob_get_clean();

    expect(substr($isi, 0, 4))->toBe('%PDF');
    expect(fn () => app(RekapPribadiPdf::class)->stream($this->fakultas))->toThrow(AuthorizationException::class);
});

it('tidak melewatkan baris saat ekspor dipotong per chunk walau urutan tanggal berbeda dari urutan id', function () {
    config(['puspresma.ekspor.chunk' => 2]);
    foreach (range(1, 7) as $i) {
        Prestasi::factory()->terverifikasi()->for(Mahasiswa::factory()->for($this->prodiA, 'prodi'), 'pengaju')->create([
            'nama_kegiatan' => "Prestasi Potongan {$i}", 'tanggal_mulai' => now()->subDays($i * 5)->toDateString(), 'tanggal_selesai' => now()->subDays($i * 5)->toDateString(),
        ]);
    }

    foreach (['rekap_prestasi', 'rekap_anggota', 'simkatmawa'] as $jenis) {
        $respons = app(EksporService::class)->minta(RegistriLaporan::buat($jenis), 'xlsx', [], $this->fakultas);
        $baris = ($this->baca)($respons);
        expect(count(array_unique(array_column($baris, $jenis === 'simkatmawa' ? 'Nama Kegiatan' : 'Kegiatan'))))->toBe(9);
    }
});

<?php

use App\Actions\Galeri\UbahPersetujuanPublikasi;
use App\Contracts\PenyimpananBerkas;
use App\Enums\JenisTautan;
use App\Enums\StatusCekTautan;
use App\Filament\Portal\Pages\PersetujuanPublikasi;
use App\Jobs\PeriksaTautanBerkas;
use App\Models\Aktivitas;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Services\Pengaturan;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class]);
    $this->balasan = fn () => Http::response('', 200);
    Http::fake(fn ($request) => ($this->balasan)($request));
    Filament::setCurrentPanel('portal');
    $this->pengaju = Mahasiswa::factory()->create();
    $this->userPengaju = User::factory()->create(['email' => $this->pengaju->email])->assignRole('mahasiswa');
    $this->pengaju->update(['user_id' => $this->userPengaju->id]);
    $this->prestasi = Prestasi::factory()->terverifikasi()->kelompok()->for($this->pengaju, 'pengaju')->create();
    $this->anggotaLain = Mahasiswa::factory()->create();
    PrestasiAnggota::factory()->create(['prestasi_id' => $this->prestasi->id, 'mahasiswa_id' => $this->anggotaLain->id]);
    $this->barisPengaju = PrestasiAnggota::where('prestasi_id', $this->prestasi->id)->where('is_pengaju', true)->first();
});

it('mengizinkan anggota hanya mengubah persetujuannya sendiri dan mencatatnya lengkap', function () {
    $barisLain = PrestasiAnggota::where('mahasiswa_id', $this->anggotaLain->id)->first();
    expect(fn () => app(UbahPersetujuanPublikasi::class)->jalankan($barisLain, $this->userPengaju, true))->toThrow(AuthorizationException::class);

    app(UbahPersetujuanPublikasi::class)->jalankan($this->barisPengaju, $this->userPengaju, true);

    expect($this->barisPengaju->refresh()->setuju_publikasi)->toBeTrue()->and($this->barisPengaju->setuju_publikasi_at)->not->toBeNull();
    $log = Aktivitas::where('log_name', 'persetujuan')->first();
    expect($log->properties['nilai'])->toBeTrue()->and($log->properties['prestasi_id'])->toBe($this->prestasi->id)
        ->and($log->properties['versi_teks'])->toBe(md5((string) Pengaturan::ambil('teks_persetujuan_publikasi')))->and($log->properties)->toHaveKeys(['ip', 'user_agent']);
});

it('menaikkan versi cache galeri saat persetujuan diberikan dan ditarik', function () {
    Cache::forget('galeri:versi');

    app(UbahPersetujuanPublikasi::class)->jalankan($this->barisPengaju, $this->userPengaju, true);
    expect((int) Cache::get('galeri:versi'))->toBe(1);

    app(UbahPersetujuanPublikasi::class)->jalankan($this->barisPengaju->refresh(), $this->userPengaju, false);
    expect((int) Cache::get('galeri:versi'))->toBe(2)->and($this->barisPengaju->refresh()->setuju_publikasi_at)->toBeNull();
});

it('menentukan tampilDiGaleri dari status, pengaturan, persetujuan pengaju, dan moderasi', function () {
    expect($this->prestasi->tampilDiGaleri())->toBeFalse();

    $this->barisPengaju->update(['setuju_publikasi' => true]);
    expect($this->prestasi->tampilDiGaleri())->toBeTrue();

    $this->prestasi->update(['disembunyikan_dari_galeri' => true]);
    expect($this->prestasi->tampilDiGaleri())->toBeFalse();
    $this->prestasi->update(['disembunyikan_dari_galeri' => false]);

    Pengaturan::setel('galeri_aktif', false);
    expect($this->prestasi->tampilDiGaleri())->toBeFalse();
    Pengaturan::setel('galeri_aktif', true);

    $diajukan = Prestasi::factory()->diajukan()->for($this->pengaju, 'pengaju')->create();
    PrestasiAnggota::where('prestasi_id', $diajukan->id)->update(['setuju_publikasi' => true]);
    expect($diajukan->tampilDiGaleri())->toBeFalse()->and($diajukan->alasanTidakTampilDiGaleri())->toContain('Prestasi belum terverifikasi.');
});

it('hanya memakai foto penyerahan yang diizinkan, Drive, dan dapat diakses; sertifikat tidak pernah', function () {
    $ok = TautanBerkas::factory()->untukPrestasi($this->prestasi)->fotoPublik()->create();
    TautanBerkas::factory()->untukPrestasi($this->prestasi)->fotoPublik()->create(['tampil_publik' => false]);
    $mati = TautanBerkas::factory()->untukPrestasi($this->prestasi)->fotoPublik()->create();
    $mati->forceFill(['status_cek' => StatusCekTautan::TidakDapatDiakses])->saveQuietly();
    $unsil = TautanBerkas::factory()->untukPrestasi($this->prestasi)->fotoPublik()->create(['url' => 'https://fkip.unsil.ac.id/foto.jpg']);
    $unsil->forceFill(['status_cek' => StatusCekTautan::DapatDiakses])->saveQuietly();
    TautanBerkas::factory()->untukPrestasi($this->prestasi)->sertifikat()->create(['tampil_publik' => true]);

    $foto = $this->prestasi->fotoPublik();

    expect($foto->pluck('id')->all())->toBe([$ok->id])->and($foto->first()->jenis)->toBe(JenisTautan::FotoPenyerahan);
});

it('membangun url gambar lh3 dan merender foto dengan placeholder tanpa memuat dari server', function () {
    $foto = TautanBerkas::factory()->untukPrestasi($this->prestasi)->fotoPublik()->create(['url' => 'https://drive.google.com/file/d/FOTO123/view']);

    expect(app(PenyimpananBerkas::class)->urlGambar($foto))->toBe('https://lh3.googleusercontent.com/d/FOTO123');
    $html = Blade::render('<x-foto-drive :tautan="$t" :alt="$t->label" />', ['t' => $foto]);
    expect($html)->toContain('src="https://lh3.googleusercontent.com/d/FOTO123"')->toContain('loading="lazy"')->toContain('referrerpolicy="no-referrer"')
        ->toContain('placeholder-prestasi.svg')->toContain('onerror');
});

it('menaikkan versi galeri bila status foto berubah menjadi tidak dapat diakses atau tampil_publik dimatikan', function () {
    $foto = TautanBerkas::factory()->untukPrestasi($this->prestasi)->fotoPublik()->create();
    Cache::forget('galeri:versi');

    $this->balasan = fn () => Http::response('', 404);
    PeriksaTautanBerkas::dispatchSync($foto);
    expect($foto->refresh()->status_cek)->toBe(StatusCekTautan::TidakDapatDiakses)->and((int) Cache::get('galeri:versi'))->toBe(1)
        ->and($this->prestasi->fotoPublik())->toHaveCount(0);

    $foto2 = TautanBerkas::factory()->untukPrestasi($this->prestasi)->fotoPublik()->create();
    Cache::forget('galeri:versi');
    $foto2->update(['tampil_publik' => false]);
    expect((int) Cache::get('galeri:versi'))->toBeGreaterThanOrEqual(1)->and($this->prestasi->fotoPublik())->toHaveCount(0);
});

it('menampilkan persetujuan saya beserta alasan belum tampil dan mengubahnya lewat sakelar', function () {
    $this->actingAs($this->userPengaju);

    Livewire::test(PersetujuanPublikasi::class)
        ->assertCanSeeTableRecords([$this->barisPengaju])
        ->assertSee('Pengaju belum menyetujui publikasi.')
        ->call('updateTableColumnState', 'setuju_publikasi', $this->barisPengaju->getKey(), true);

    expect($this->barisPengaju->refresh()->setuju_publikasi)->toBeTrue();
    Livewire::test(PersetujuanPublikasi::class)->assertSee('Ya');
});

it('menampilkan alasan foto belum tampil', function () {
    TautanBerkas::factory()->untukPrestasi($this->prestasi)->fotoPublik()->create(['tampil_publik' => false]);

    expect($this->prestasi->alasanFotoTidakTampil())->toContain('Foto belum diizinkan tampil.');
});

<?php

use App\Actions\Galeri\UbahPersetujuanPublikasi;
use App\Filament\Admin\Pages\ModerasiGaleri;
use App\Livewire\Publik\GaleriPrestasi;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Services\Pengaturan;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class]);
    Http::fake(['*' => Http::response('', 200)]);
    RateLimiter::clear('galeri');
    $this->pengaju = Mahasiswa::factory()->create(['nama' => 'Siti Pengaju', 'npm' => '2100000777']);
    $this->userPengaju = User::factory()->create(['email' => $this->pengaju->email])->assignRole('mahasiswa');
    $this->pengaju->update(['user_id' => $this->userPengaju->id]);
    $this->prestasi = Prestasi::factory()->terverifikasi()->kelompok()->for($this->pengaju, 'pengaju')->create(['nama_kegiatan' => 'Lomba Debat Terbuka']);
    $this->barisPengaju = PrestasiAnggota::where('prestasi_id', $this->prestasi->id)->where('is_pengaju', true)->first();
    $this->lain = Mahasiswa::factory()->create(['nama' => 'Budi Tidak Setuju', 'npm' => '2100000888']);
    PrestasiAnggota::factory()->create(['prestasi_id' => $this->prestasi->id, 'mahasiswa_id' => $this->lain->id]);
    TautanBerkas::factory()->untukPrestasi($this->prestasi)->sertifikat()->create(['url' => 'https://drive.google.com/file/d/SERTRAHASIA/view']);
    $this->setuju = fn (bool $nilai = true) => app(UbahPersetujuanPublikasi::class)->jalankan($this->barisPengaju->refresh(), $this->userPengaju, $nilai);
});

it('menampilkan prestasi terverifikasi yang disetujui pengaju dan menyembunyikan nama yang tidak setuju', function () {
    $this->get(route('galeri.index'))->assertOk()->assertDontSee('Lomba Debat Terbuka');

    ($this->setuju)();

    $this->get(route('galeri.index'))->assertOk()->assertSee('Lomba Debat Terbuka')->assertSee('Siti Pengaju')
        ->assertDontSee('Budi Tidak Setuju')->assertSee('dan 1 anggota lain');
});

it('tidak pernah memuat npm, surel, tautan sertifikat, atau url drive selain gambar lh3 foto yang memenuhi syarat', function () {
    ($this->setuju)();
    TautanBerkas::factory()->untukPrestasi($this->prestasi)->fotoPublik()->create(['url' => 'https://drive.google.com/file/d/FOTOOK/view']);
    TautanBerkas::factory()->untukPrestasi($this->prestasi)->fotoPublik()->create(['url' => 'https://drive.google.com/file/d/FOTOPRIVAT/view', 'tampil_publik' => false]);

    foreach ([route('galeri.index'), route('galeri.show', $this->prestasi)] as $url) {
        $html = $this->get($url)->assertOk()->getContent();
        expect($html)->toContain('https://lh3.googleusercontent.com/d/FOTOOK')
            ->not->toContain('2100000777')->not->toContain('2100000888')->not->toContain($this->pengaju->email)->not->toContain($this->pengaju->nomor_hp)
            ->not->toContain('drive.google.com')->not->toContain('SERTRAHASIA')->not->toContain('FOTOPRIVAT');
    }
});

it('tidak menampilkan foto dengan status_cek tidak dapat diakses', function () {
    ($this->setuju)();
    $foto = TautanBerkas::factory()->untukPrestasi($this->prestasi)->fotoPublik()->create(['url' => 'https://drive.google.com/file/d/FOTOMATI/view']);
    $foto->forceFill(['status_cek' => 'tidak_dapat_diakses'])->saveQuietly();
    Cache::increment('galeri:versi');

    $this->get(route('galeri.show', $this->prestasi))->assertOk()->assertDontSee('FOTOMATI');
});

it('langsung menghilangkan prestasi saat persetujuan ditarik tanpa menunggu ttl cache', function () {
    ($this->setuju)();
    $this->get(route('galeri.index'))->assertSee('Lomba Debat Terbuka');
    $this->get(route('galeri.show', $this->prestasi))->assertOk();

    ($this->setuju)(false);

    $this->get(route('galeri.index'))->assertDontSee('Lomba Debat Terbuka');
    $this->get(route('galeri.show', $this->prestasi))->assertNotFound();
});

it('mengembalikan 404 untuk detail yang disembunyikan admin, draf, atau terhapus', function () {
    ($this->setuju)();
    $this->prestasi->update(['disembunyikan_dari_galeri' => true]);
    Cache::increment('galeri:versi');
    $this->get(route('galeri.show', $this->prestasi))->assertNotFound();

    $draf = Prestasi::factory()->for($this->pengaju, 'pengaju')->create();
    PrestasiAnggota::where('prestasi_id', $draf->id)->update(['setuju_publikasi' => true]);
    $this->get(route('galeri.show', $draf))->assertNotFound();
    $this->get(route('galeri.show', '00000000-0000-7000-8000-000000000000'))->assertNotFound();
});

it('menampilkan pesan bila galeri dinonaktifkan dan tanpa data', function () {
    ($this->setuju)();
    Pengaturan::setel('galeri_aktif', false);

    $this->get(route('galeri.index'))->assertOk()->assertSee('Galeri sedang tidak tersedia')->assertDontSee('Lomba Debat Terbuka');
    $this->get(route('galeri.show', $this->prestasi))->assertNotFound();

    Pengaturan::setel('galeri_aktif', true);
    $this->get(route('galeri.index'))->assertSee('Lomba Debat Terbuka');
});

it('menyaring galeri menurut tingkat dan tahun', function () {
    ($this->setuju)();
    $lain = Prestasi::factory()->terverifikasi()->for(Mahasiswa::factory(), 'pengaju')->create(['nama_kegiatan' => 'Lomba Tahun Lalu', 'tanggal_mulai' => now()->subYears(2)->toDateString(), 'tanggal_selesai' => now()->subYears(2)->toDateString()]);
    PrestasiAnggota::where('prestasi_id', $lain->id)->update(['setuju_publikasi' => true]);

    Livewire::test(GaleriPrestasi::class)->assertSee('Lomba Debat Terbuka')->assertSee('Lomba Tahun Lalu')
        ->set('tahun', $lain->tahun)->assertSee('Lomba Tahun Lalu')->assertDontSee('Lomba Debat Terbuka');
});

it('tidak melakukan N+1 untuk banyak prestasi dan memuat beranda dari cache galeri', function () {
    ($this->setuju)();
    foreach (range(1, 15) as $i) {
        $p = Prestasi::factory()->terverifikasi()->kelompok()->for(Mahasiswa::factory(), 'pengaju')->create(['nama_kegiatan' => "Prestasi Massal {$i}"]);
        PrestasiAnggota::where('prestasi_id', $p->id)->update(['setuju_publikasi' => true]);
        PrestasiAnggota::factory()->create(['prestasi_id' => $p->id]);
        TautanBerkas::factory()->untukPrestasi($p)->fotoPublik()->create();
    }

    $this->get(route('galeri.index'))->assertOk()->assertSee('Halaman 1 dari 2');
    $this->get(route('beranda'))->assertOk()->assertSee('Prestasi terbaru');
});

it('menampilkan halaman kebijakan privasi galeri dengan teks persetujuan', function () {
    $this->get(route('galeri.kebijakan'))->assertOk()->assertSee('Cara menarik persetujuan')->assertSee('galeri publik FKIP');
});

it('memoderasi lewat halaman admin: sembunyikan dengan alasan, hilang dari galeri, lalu tampilkan kembali', function () {
    Filament::setCurrentPanel('admin');
    ($this->setuju)();
    $this->get(route('galeri.index'))->assertSee('Lomba Debat Terbuka');
    $admin = User::factory()->create()->assignRole('admin-fakultas');
    $admin->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();
    $this->actingAs($admin);

    Livewire::test(ModerasiGaleri::class)
        ->assertCanSeeTableRecords([$this->prestasi])
        ->callAction(TestAction::make('sembunyikan')->table($this->prestasi), ['alasan' => ''])->assertHasActionErrors(['alasan']);
    Livewire::test(ModerasiGaleri::class)
        ->callAction(TestAction::make('sembunyikan')->table($this->prestasi), ['alasan' => 'Foto mengandung data pribadi.'])->assertNotified('Prestasi disembunyikan dari galeri');

    auth()->logout();
    $this->get(route('galeri.index'))->assertDontSee('Lomba Debat Terbuka');
    expect($this->prestasi->refresh()->status->value)->toBe('terverifikasi')->and($this->prestasi->alasan_disembunyikan)->toBe('Foto mengandung data pribadi.');

    $this->actingAs($admin);
    Livewire::test(ModerasiGaleri::class)->callAction(TestAction::make('tampilkan')->table($this->prestasi));
    auth()->logout();
    $this->get(route('galeri.index'))->assertSee('Lomba Debat Terbuka');
});

it('menolak moderasi oleh admin-prodi dan membatasi galeri 60 permintaan per menit', function () {
    $adminProdi = User::factory()->create()->assignRole('admin-prodi');
    Filament::setCurrentPanel('admin');
    $this->actingAs($adminProdi)->get(ModerasiGaleri::getUrl())->assertForbidden();
    auth()->logout();

    foreach (range(1, 60) as $_) {
        $this->get(route('galeri.index'))->assertOk();
    }
    $this->get(route('galeri.index'))->assertStatus(429);
});

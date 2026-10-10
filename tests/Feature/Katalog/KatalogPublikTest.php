<?php

use App\Actions\Katalog\UbahMinatKompetisi;
use App\Filament\Portal\Pages\KatalogKompetisi as HalamanKatalog;
use App\Filament\Portal\Resources\PrestasiSayaResource\Pages\CreatePrestasiSaya;
use App\Livewire\Publik\KatalogKompetisi;
use App\Models\Capaian;
use App\Models\JenisKegiatan;
use App\Models\Kompetisi;
use App\Models\KompetisiMinat;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\Prodi;
use App\Models\User;
use Database\Factories\Concerns\MasterContoh;
use Database\Seeders\CapaianSeeder;
use Database\Seeders\JenisKegiatanSeeder;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Database\Seeders\TingkatSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    RateLimiter::clear('katalog-publik');
});

it('menampilkan hanya kompetisi terbit yang masih dibuka di halaman publik tanpa login', function () {
    Kompetisi::factory()->terbit()->create(['judul' => 'Lomba Terbit Dibuka']);
    Kompetisi::factory()->create(['judul' => 'Lomba Masih Draf']);
    Kompetisi::factory()->diarsipkan()->create(['judul' => 'Lomba Sudah Arsip']);
    Kompetisi::factory()->terbit()->create(['judul' => 'Lomba Tenggat Lewat', 'tenggat_pendaftaran' => now()->subHour()]);

    $this->get(route('kompetisi.index'))->assertOk()
        ->assertSee('Lomba Terbit Dibuka')->assertDontSee('Lomba Masih Draf')->assertDontSee('Lomba Sudah Arsip')->assertDontSee('Lomba Tenggat Lewat');
});

it('menyaring berdasarkan tingkat, mencari judul, dan menandai tenggat 3 hari', function () {
    $lokal = MasterContoh::tingkat('lokal');
    Kompetisi::factory()->terbit()->create(['judul' => 'Lomba Nasional Satu', 'tingkat_id' => MasterContoh::tingkat('nasional')->id, 'tenggat_pendaftaran' => now()->addDays(10)]);
    Kompetisi::factory()->terbit()->create(['judul' => 'Lomba Lokal Dua', 'tingkat_id' => $lokal->id, 'tenggat_pendaftaran' => now()->addDays(2)]);

    Livewire::test(KatalogKompetisi::class)
        ->set('tingkat', $lokal->id)->assertSee('Lomba Lokal Dua')->assertDontSee('Lomba Nasional Satu')->assertSee('hari lagi')
        ->set('tingkat', null)->set('q', 'Nasional Satu')->assertSee('Lomba Nasional Satu')->assertDontSee('Lomba Lokal Dua');
});

it('membuang cache daftar saat katalog berubah', function () {
    $k = Kompetisi::factory()->terbit()->create(['judul' => 'Judul Lama']);
    Livewire::test(KatalogKompetisi::class)->assertSee('Judul Lama');

    $k->update(['judul' => 'Judul Baru']);

    Livewire::test(KatalogKompetisi::class)->assertSee('Judul Baru')->assertDontSee('Judul Lama');
});

it('menampilkan detail terbit, label ditutup untuk arsip, dan 404 untuk draf', function () {
    $terbit = Kompetisi::factory()->terbit()->create(['judul' => 'Detail Terbit', 'deskripsi' => '<p>Isi <strong>penting</strong></p>']);
    $arsip = Kompetisi::factory()->diarsipkan()->create(['judul' => 'Detail Arsip']);
    $draf = Kompetisi::factory()->create(['judul' => 'Detail Draf']);

    $this->get(route('kompetisi.show', $terbit->slug))->assertOk()->assertSee('Detail Terbit')->assertSee('<strong>penting</strong>', false)->assertSee('Daftar');
    $this->get(route('kompetisi.show', $arsip->slug))->assertOk()->assertSee('Pendaftaran ditutup');
    $this->get(route('kompetisi.show', $draf->slug))->assertNotFound();
});

it('menampilkan beranda dengan kompetisi terdekat', function () {
    Kompetisi::factory()->terbit()->create(['judul' => 'Tampil Di Beranda']);

    $this->get(route('beranda'))->assertOk()->assertSee('Tampil Di Beranda');
});

it('membatasi halaman katalog 60 permintaan per menit per IP', function () {
    foreach (range(1, 60) as $_) {
        $this->get(route('kompetisi.index'))->assertOk();
    }

    $this->get(route('kompetisi.index'))->assertStatus(429);
});

it('mengizinkan mahasiswa berminat sekali dan membatalkannya, menolak dosen dan kompetisi ditutup', function () {
    $mhs = Mahasiswa::factory()->create();
    $user = User::factory()->create(['email' => $mhs->email])->assignRole('mahasiswa');
    $mhs->update(['user_id' => $user->id]);
    $k = Kompetisi::factory()->terbit()->create();

    app(UbahMinatKompetisi::class)->jalankan($k, $user, true);
    app(UbahMinatKompetisi::class)->jalankan($k, $user, true);
    expect(KompetisiMinat::count())->toBe(1);

    expect(fn () => KompetisiMinat::create(['kompetisi_id' => $k->id, 'mahasiswa_id' => $mhs->id]))->toThrow(UniqueConstraintViolationException::class);

    app(UbahMinatKompetisi::class)->jalankan($k, $user, false);
    expect(KompetisiMinat::count())->toBe(0);

    expect(fn () => app(UbahMinatKompetisi::class)->jalankan($k, User::factory()->create()->assignRole('dosen'), true))->toThrow(AuthorizationException::class);
});

it('menampilkan aksi berminat dan penanda untuk prodi di portal', function () {
    Filament::setCurrentPanel('portal');
    $prodi = Prodi::factory()->create();
    $mhs = Mahasiswa::factory()->for($prodi)->create();
    $user = User::factory()->create(['email' => $mhs->email, 'prodi_id' => $prodi->id])->assignRole('mahasiswa');
    $mhs->update(['user_id' => $user->id]);
    $k = Kompetisi::factory()->terbit()->create();
    $k->prodiSasaran()->sync([$prodi->id]);
    $this->actingAs($user);

    Livewire::test(HalamanKatalog::class)
        ->assertCanSeeTableRecords([$k])->assertSee('Untuk prodi Anda')
        ->callAction(TestAction::make('berminat')->table($k))->assertNotified('Ditandai berminat')
        ->assertActionHidden(TestAction::make('berminat')->table($k))->assertActionVisible(TestAction::make('batalMinat')->table($k));
    expect(KompetisiMinat::where('mahasiswa_id', $mhs->id)->count())->toBe(1);
});

it('menyimpan kompetisi_id saat prestasi dibuat dari katalog', function () {
    Filament::setCurrentPanel('portal');
    $this->seed([JenisKegiatanSeeder::class, TingkatSeeder::class, CapaianSeeder::class, PengaturanSeeder::class]);
    $mhs = Mahasiswa::factory()->create(['angkatan' => 2022]);
    $user = User::factory()->create(['email' => $mhs->email])->assignRole('mahasiswa');
    $mhs->update(['user_id' => $user->id]);
    $k = Kompetisi::factory()->terbit()->create(['judul' => 'Olimpiade Dari Katalog', 'jenis_kegiatan_id' => JenisKegiatan::firstWhere('kode', 'kompetisi')->id]);
    $this->actingAs($user);

    Livewire::test(CreatePrestasiSaya::class)
        ->fillForm(['kompetisi_id' => $k->id])
        ->assertFormSet(['nama_kegiatan' => 'Olimpiade Dari Katalog', 'penyelenggara' => $k->penyelenggara, 'tingkat_id' => $k->tingkat_id])
        ->fillForm([
            'bidang' => 'akademik', 'tanggal_mulai' => now()->subDays(9)->toDateString(), 'tanggal_selesai' => now()->subDays(8)->toDateString(), 'moda' => 'daring',
            'capaian_id' => Capaian::where('jenis_kegiatan_id', $k->jenis_kegiatan_id)->firstWhere('kode', 'juara-1')->id, 'bentuk' => 'individu', 'jumlah_pt_peserta' => 20,
        ])
        ->call('create')->assertHasNoFormErrors();

    expect(Prestasi::firstWhere('nama_kegiatan', 'Olimpiade Dari Katalog')->kompetisi_id)->toBe($k->id);
});

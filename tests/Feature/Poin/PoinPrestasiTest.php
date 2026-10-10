<?php

use App\Actions\Poin\HitungPoinPrestasi;
use App\Actions\Poin\HitungUlangPoinMassal;
use App\Actions\Prestasi\BukaVerifikasiPrestasi;
use App\Actions\Prestasi\VerifikasiPrestasi;
use App\Filament\Admin\Pages\MatriksBobotPoin;
use App\Filament\Admin\Pages\PeringkatPoin;
use App\Filament\Portal\Pages\PoinSaya;
use App\Models\Aktivitas;
use App\Models\BobotPoin;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use App\Models\Prodi;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Services\Pengaturan;
use App\Services\PeringkatPoin as LayananPeringkat;
use App\Support\TahunAkademik;
use Carbon\CarbonImmutable;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class]);
    Http::fake(['*' => Http::response('', 200)]);
    Filament::setCurrentPanel('admin');
    Pengaturan::setel('poin_aktif', true);
    $this->prodi = Prodi::factory()->create();
    $this->fakultas = User::factory()->create()->assignRole('admin-fakultas');
    $this->prestasi = Prestasi::factory()->kelompok()->diajukan()->for(Mahasiswa::factory()->for($this->prodi), 'pengaju')->create();
    TautanBerkas::factory()->untukPrestasi($this->prestasi)->sertifikat()->create();
    foreach (range(1, 2) as $_) {
        PrestasiAnggota::factory()->create(['prestasi_id' => $this->prestasi->id, 'mahasiswa_id' => Mahasiswa::factory()->for($this->prodi)->create()->id]);
    }
    $this->bobot = BobotPoin::create([
        'jenis_kegiatan_id' => $this->prestasi->jenis_kegiatan_id, 'tingkat_id' => $this->prestasi->tingkat_id, 'capaian_id' => $this->prestasi->capaian_id,
        'poin' => '40.00', 'berlaku_mulai' => '2020-01-01', 'dasar_penetapan' => 'SK uji',
    ]);
    $this->poin = fn (): array => PrestasiAnggota::where('prestasi_id', $this->prestasi->id)->pluck('poin')->map(fn ($p) => $p === null ? null : (string) $p)->all();
});

it('memberi setiap anggota poin penuh pada mode penuh saat diverifikasi', function () {
    app(VerifikasiPrestasi::class)->jalankan($this->prestasi, $this->fakultas);

    expect(($this->poin)())->toBe(['40.00', '40.00', '40.00']);
});

it('membagi poin pada mode dibagi dengan pembulatan ke bawah dua desimal', function () {
    Pengaturan::setel('mode_poin_tim', 'dibagi');

    app(VerifikasiPrestasi::class)->jalankan($this->prestasi, $this->fakultas);

    expect(($this->poin)())->toBe(['13.33', '13.33', '13.33']);
});

it('tidak mengisi poin bila poin_aktif nonaktif dan mengisi 0.00 dengan log bila bobot tidak ada', function () {
    Pengaturan::setel('poin_aktif', false);
    expect(app(HitungPoinPrestasi::class)->jalankan($this->prestasi))->toBeFalse()->and(($this->poin)())->toBe([null, null, null]);

    Pengaturan::setel('poin_aktif', true);
    $this->bobot->delete();
    app(HitungPoinPrestasi::class)->jalankan($this->prestasi);

    expect(($this->poin)())->toBe(['0.00', '0.00', '0.00'])
        ->and(Aktivitas::where('log_name', 'poin')->where('event', 'bobot_tidak_ditemukan')->count())->toBe(1);
});

it('mengosongkan poin saat verifikasi dibuka dan mengikuti bobot baru saat dihitung ulang', function () {
    app(VerifikasiPrestasi::class)->jalankan($this->prestasi, $this->fakultas);
    $this->bobot->update(['poin' => '50.00']);
    expect(($this->poin)())->toBe(['40.00', '40.00', '40.00']);

    $jumlah = app(HitungUlangPoinMassal::class)->jalankan($this->fakultas);
    expect($jumlah)->toBe(1)->and(($this->poin)())->toBe(['50.00', '50.00', '50.00'])
        ->and(Aktivitas::where('log_name', 'poin')->where('event', 'hitung_ulang')->first()->properties['jumlah_prestasi'])->toBe(1);

    app(BukaVerifikasiPrestasi::class)->jalankan($this->prestasi->refresh(), $this->fakultas, 'Perlu koreksi tingkat kegiatan.');
    expect(($this->poin)())->toBe([null, null, null]);
});

it('menolak hitung ulang oleh pengguna tanpa izin dan menyediakan tombolnya di halaman matriks', function () {
    expect(fn () => app(HitungUlangPoinMassal::class)->jalankan(User::factory()->create()->assignRole('admin-prodi')))->toThrow(AuthorizationException::class);

    app(VerifikasiPrestasi::class)->jalankan($this->prestasi, $this->fakultas);
    $this->fakultas->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();
    $this->actingAs($this->fakultas);
    Livewire::test(MatriksBobotPoin::class)->callAction('hitungUlang')->assertNotified('1 prestasi dihitung ulang');
});

it('membatasi peringkat admin-prodi pada anggota prodinya dan menjumlahkan poin dalam periode', function () {
    app(VerifikasiPrestasi::class)->jalankan($this->prestasi, $this->fakultas);
    $lain = Prestasi::factory()->terverifikasi()->for(Mahasiswa::factory()->for(Prodi::factory()), 'pengaju')->create();
    PrestasiAnggota::where('prestasi_id', $lain->id)->update(['poin' => '10.00']);
    $adminProdi = User::factory()->create(['prodi_id' => $this->prodi->id])->assignRole('admin-prodi');

    $semua = app(LayananPeringkat::class)->hitung($this->fakultas);
    $prodi = app(LayananPeringkat::class)->hitung($adminProdi);

    expect($semua)->toHaveCount(4)->and($semua->first()['total_poin'])->toBe('40.00')
        ->and($prodi)->toHaveCount(3)->and($prodi->every(fn ($b) => $b['mahasiswa']->prodi_id === $this->prodi->id))->toBeTrue();
    expect(app(LayananPeringkat::class)->hitung($this->fakultas, ['dari' => now()->addYear()->toDateString()]))->toHaveCount(0);

    $adminProdi->forceFill([])->save();
    $this->actingAs($adminProdi);
    Livewire::test(PeringkatPoin::class)->assertSuccessful()->assertSee($this->prestasi->pengaju->npm);
});

it('menghitung tahun akademik dari bulan awal', function () {
    expect(TahunAkademik::dari(CarbonImmutable::parse('2026-09-10'), 8))->toBe('2026/2027')
        ->and(TahunAkademik::dari(CarbonImmutable::parse('2027-03-01'), 8))->toBe('2026/2027')
        ->and(TahunAkademik::dari(CarbonImmutable::parse('2026-07-31'), 8))->toBe('2025/2026');
});

it('menampilkan total poin saya per tahun dan tahun akademik dan menyembunyikannya bila poin nonaktif', function () {
    $mhs = $this->prestasi->pengaju;
    $user = User::factory()->create(['email' => $mhs->email])->assignRole('mahasiswa');
    $mhs->update(['user_id' => $user->id]);
    app(VerifikasiPrestasi::class)->jalankan($this->prestasi, $this->fakultas);
    Filament::setCurrentPanel('portal');
    $this->actingAs($user);

    expect(PoinSaya::canAccess())->toBeTrue();
    Livewire::test(PoinSaya::class)->assertSee('40.00')->assertSee((string) $this->prestasi->tahun);

    Pengaturan::setel('poin_aktif', false);
    expect(PoinSaya::canAccess())->toBeFalse()->and(PoinSaya::shouldRegisterNavigation())->toBeFalse();
    $this->get(PoinSaya::getUrl())->assertForbidden();
});

it('tidak memakai float untuk poin pada kode aplikasi poin', function () {
    foreach (['app/Actions/Poin/HitungPoinPrestasi.php', 'app/Services/PeringkatPoin.php', 'app/Services/PenghitungPoin.php'] as $berkas) {
        expect(file_get_contents(base_path($berkas)))->not->toMatch('/\(float\)|floatval|round\(/');
    }
});

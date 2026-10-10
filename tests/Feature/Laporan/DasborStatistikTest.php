<?php

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Widgets\RingkasanPrestasi;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use App\Models\Prodi;
use App\Models\User;
use App\Services\StatistikPrestasi;
use App\Support\FilterDasbor;
use Database\Factories\Concerns\MasterContoh;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    Filament::setCurrentPanel('admin');
    $this->prodiA = Prodi::factory()->create(['nama' => 'Prodi A']);
    $this->prodiB = Prodi::factory()->create(['nama' => 'Prodi B']);
    $this->tim = Prestasi::factory()->terverifikasi()->kelompok()->for(Mahasiswa::factory()->for($this->prodiA), 'pengaju')->create();
    PrestasiAnggota::factory()->create(['prestasi_id' => $this->tim->id, 'mahasiswa_id' => Mahasiswa::factory()->for($this->prodiB)->create()->id]);
});

it('menghitung prestasi tim lintas prodi di setiap prodi anggota tetapi sekali di total fakultas', function () {
    $statistik = StatistikPrestasi::untuk();

    expect($statistik->perProdi())->toBe(['Prodi A' => 1, 'Prodi B' => 1])
        ->and($statistik->ringkasan()['total_prestasi'])->toBe(1)
        ->and($statistik->ringkasan()['total_mahasiswa'])->toBe(2);
});

it('tidak menghitung prestasi draf, diajukan, atau terhapus', function () {
    Prestasi::factory()->for(Mahasiswa::factory()->for($this->prodiA), 'pengaju')->create();
    Prestasi::factory()->diajukan()->create();
    Prestasi::factory()->terverifikasi()->create()->delete();

    expect(StatistikPrestasi::untuk()->ringkasan()['total_prestasi'])->toBe(1);
});

it('menghitung ringkasan nasional, internasional, juara, per tingkat dan per bulan', function () {
    $juara = MasterContoh::capaian('juara-1');
    $peserta = MasterContoh::capaian('peserta');
    Prestasi::factory()->terverifikasi()->internasional()->create(['capaian_id' => $juara->id, 'tanggal_mulai' => '2026-02-01', 'tanggal_selesai' => '2026-02-02']);
    $this->tim->update(['capaian_id' => $peserta->id, 'tanggal_mulai' => '2026-03-01', 'tanggal_selesai' => '2026-03-05']);
    $this->tim->tingkat_id === MasterContoh::tingkat('nasional')->id || $this->tim->update(['tingkat_id' => MasterContoh::tingkat('nasional')->id]);

    $s = StatistikPrestasi::untuk(2026);
    $r = $s->ringkasan();

    expect($r['total_prestasi'])->toBe(2)->and($r['internasional'])->toBe(1)->and($r['nasional'])->toBe(1)->and($r['juara'])->toBe(1)->and($r['persen_juara'])->toBe(50.0)
        ->and($s->perTingkat())->toHaveKeys(['Nasional', 'Internasional'])
        ->and($s->perBulan()[2])->toBe(1)->and($s->perBulan()[3])->toBe(1)->and($s->perBulan())->toHaveCount(12)
        ->and(StatistikPrestasi::untuk(2026)->trenTahunan())->toHaveKeys([2022, 2026]);
});

it('memakai cache pada panggilan kedua (tanpa kueri) dan memperbarui saat versi naik', function () {
    StatistikPrestasi::untuk()->ringkasan();

    DB::enableQueryLog();
    DB::flushQueryLog();
    $ulang = StatistikPrestasi::untuk()->ringkasan();
    expect(DB::getQueryLog())->toBe([])->and($ulang['total_prestasi'])->toBe(1);

    Prestasi::factory()->terverifikasi()->create();
    expect(StatistikPrestasi::untuk()->ringkasan()['total_prestasi'])->toBe(1);

    Cache::increment('statistik:versi');
    expect(StatistikPrestasi::untuk()->ringkasan()['total_prestasi'])->toBe(2);
});

it('mengunci admin-prodi ke prodinya dan membiarkan pimpinan memilih semua prodi', function () {
    $adminProdi = User::factory()->create(['prodi_id' => $this->prodiA->id])->assignRole('admin-prodi');
    $pimpinan = User::factory()->create()->assignRole('pimpinan');

    expect(FilterDasbor::prodiEfektif($adminProdi, $this->prodiB->id))->toBe($this->prodiA->id)
        ->and(FilterDasbor::prodiEfektif($adminProdi, null))->toBe($this->prodiA->id)
        ->and(FilterDasbor::prodiEfektif($pimpinan, $this->prodiB->id))->toBe($this->prodiB->id)
        ->and(FilterDasbor::prodiEfektif($pimpinan, null))->toBeNull();
});

it('menampilkan dasbor dan widget ringkasan untuk pimpinan dan admin-prodi', function () {
    $pimpinan = User::factory()->create()->assignRole('pimpinan');
    $this->actingAs($pimpinan)->get(Dashboard::getUrl())->assertOk()->assertSee('Satu prestasi tim lintas prodi dihitung di setiap prodi anggota');

    $adminProdi = User::factory()->create(['prodi_id' => $this->prodiA->id])->assignRole('admin-prodi');
    $this->actingAs($adminProdi);
    Livewire::test(RingkasanPrestasi::class, ['pageFilters' => ['prodi_id' => $this->prodiB->id]])->assertSuccessful()->assertSee('Prestasi terverifikasi');
});

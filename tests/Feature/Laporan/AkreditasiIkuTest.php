<?php

use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\Prodi;
use App\Models\User;
use App\Services\Ekspor\EksporService;
use App\Services\Ekspor\RegistriLaporan;
use Database\Factories\Concerns\MasterContoh;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Database\Seeders\TingkatSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\SimpleExcel\SimpleExcelReader;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class, TingkatSeeder::class]);
    Storage::fake('tmp');
    $this->prodi = Prodi::factory()->create();
    $this->fakultas = User::factory()->create()->assignRole('admin-fakultas');
    $this->adminProdi = User::factory()->create(['prodi_id' => $this->prodi->id])->assignRole('admin-prodi');
    $this->mhs = Mahasiswa::factory()->for($this->prodi)->create();
    $this->buat = fn (int $tahun, string $tingkat, string $capaian = 'juara-1', string $bidang = 'akademik', ?Mahasiswa $m = null) => Prestasi::factory()->terverifikasi()
        ->for($m ?? $this->mhs, 'pengaju')->create([
            'tingkat_id' => MasterContoh::tingkat($tingkat)->id, 'capaian_id' => MasterContoh::capaian($capaian)->id, 'bidang' => $bidang,
            'tanggal_mulai' => "{$tahun}-05-01", 'tanggal_selesai' => "{$tahun}-05-02",
        ]);
    $this->bukaBuku = function ($respons, array $lembar): array {
        ob_start();
        $respons->sendContent();
        $isi = ob_get_clean();
        $path = Storage::disk('local')->path('uji-buku-'.uniqid().'.xlsx');
        file_put_contents($path, $isi);
        $hasil = [];
        foreach ($lembar as $nama) {
            $hasil[$nama] = SimpleExcelReader::create($path)->fromSheetName($nama)->getRows()->toArray();
        }
        unlink($path);

        return $hasil;
    };
});

it('menghitung matriks akreditasi TS-2, TS-1, TS per tingkat dan kelompok serta lampiran di sheet kedua', function () {
    foreach ([2024, 2024, 2025] as $t) {
        ($this->buat)($t, 'nasional');
    }
    ($this->buat)(2026, 'nasional', 'juara-1', 'non_akademik');
    ($this->buat)(2026, 'internasional');
    ($this->buat)(2020, 'lokal');
    ($this->buat)(2025, 'lokal', 'juara-1', 'akademik', Mahasiswa::factory()->for(Prodi::factory())->create());

    $buku = ($this->bukaBuku)(app(EksporService::class)->minta(RegistriLaporan::buat('akreditasi'), 'xlsx', ['prodi_id' => $this->prodi->id, 'tahun' => 2026], $this->fakultas), ['Statistik', 'Lampiran']);

    $stat = collect($buku['Statistik']);
    $nasionalAkademik = $stat->firstWhere(fn ($b) => $b['Tingkat'] === 'Nasional' && $b['Kelompok'] === 'Akademik');
    $nasionalNon = $stat->firstWhere(fn ($b) => str_starts_with($b['Tingkat'], 'Nasional') && $b['Kelompok'] === 'Non-akademik');
    $internasional = $stat->firstWhere(fn ($b) => $b['Tingkat'] === 'Internasional' && $b['Kelompok'] === 'Akademik');
    $lokal = $stat->firstWhere(fn ($b) => str_starts_with($b['Tingkat'], 'Lokal') && $b['Kelompok'] === 'Akademik');

    expect($stat)->toHaveCount(8)
        ->and([$nasionalAkademik['TS-2 (2024)'], $nasionalAkademik['TS-1 (2025)'], $nasionalAkademik['TS (2026)'], $nasionalAkademik['Jumlah']])->toBe([2, 1, 0, 3])
        ->and($nasionalNon['TS (2026)'])->toBe(1)->and($internasional['Jumlah'])->toBe(1)->and($lokal['Jumlah'])->toBe(0)
        ->and($buku['Lampiran'])->toHaveCount(5);
});

it('mewajibkan prodi pada LAP-03 dan mengunci admin-prodi ke prodinya', function () {
    expect(fn () => app(EksporService::class)->minta(RegistriLaporan::buat('akreditasi'), 'xlsx', ['tahun' => 2026], $this->fakultas))->toThrow(ValidationException::class, 'Pilih prodi');

    ($this->buat)(2026, 'nasional');
    ($this->buat)(2026, 'nasional', 'juara-1', 'akademik', Mahasiswa::factory()->for(Prodi::factory())->create());
    $buku = ($this->bukaBuku)(app(EksporService::class)->minta(RegistriLaporan::buat('akreditasi'), 'xlsx', ['prodi_id' => Prodi::factory()->create()->id, 'tahun' => 2026], $this->adminProdi), ['Statistik', 'Lampiran']);

    expect(collect($buku['Statistik'])->sum('Jumlah'))->toBe(1);
});

it('menghitung mahasiswa unik pada IKU sekali walau punya dua prestasi nasional', function () {
    ($this->buat)(2026, 'nasional');
    ($this->buat)(2026, 'nasional', 'juara-2');
    ($this->buat)(2026, 'internasional');
    ($this->buat)(2026, 'lokal');
    ($this->buat)(2026, 'nasional', 'peserta');
    $lain = Mahasiswa::factory()->for(Prodi::factory())->create();
    ($this->buat)(2026, 'nasional', 'juara-1', 'akademik', $lain);

    $buku = ($this->bukaBuku)(app(EksporService::class)->minta(RegistriLaporan::buat('iku'), 'xlsx', ['tahun' => 2026], $this->fakultas), ['Ringkasan', 'Daftar Mahasiswa']);
    $ringkasan = collect($buku['Ringkasan']);

    expect($ringkasan->firstWhere(fn ($b) => str_starts_with($b['Prodi'], 'TOTAL'))['Jumlah Mahasiswa Unik'])->toBe(2)
        ->and($ringkasan->firstWhere('Prodi', $this->prodi->nama)['Jumlah Mahasiswa Unik'])->toBe(1)
        ->and($buku['Daftar Mahasiswa'])->toHaveCount(4);
});

it('mengubah tingkat minimal ke internasional atau mematikan hanya-juara mengubah jumlah', function () {
    ($this->buat)(2026, 'nasional');
    ($this->buat)(2026, 'internasional', 'juara-3', 'akademik', Mahasiswa::factory()->for(Prodi::factory())->create());
    ($this->buat)(2026, 'nasional', 'peserta', 'akademik', Mahasiswa::factory()->for(Prodi::factory())->create());
    $total = fn (array $filter) => collect(($this->bukaBuku)(app(EksporService::class)->minta(RegistriLaporan::buat('iku'), 'xlsx', ['tahun' => 2026] + $filter, $this->fakultas), ['Ringkasan'])['Ringkasan'])
        ->firstWhere(fn ($b) => str_starts_with($b['Prodi'], 'TOTAL'))['Jumlah Mahasiswa Unik'];

    expect($total([]))->toBe(2)
        ->and($total(['tingkat_minimal' => 'internasional']))->toBe(1)
        ->and($total(['hanya_juara' => false]))->toBe(3);
});

it('menjadikan admin-prodi hanya melihat mahasiswa prodinya pada daftar IKU', function () {
    ($this->buat)(2026, 'nasional');
    ($this->buat)(2026, 'nasional', 'juara-1', 'akademik', Mahasiswa::factory()->for(Prodi::factory())->create());

    $buku = ($this->bukaBuku)(app(EksporService::class)->minta(RegistriLaporan::buat('iku'), 'xlsx', ['tahun' => 2026], $this->adminProdi), ['Ringkasan', 'Daftar Mahasiswa']);

    expect($buku['Daftar Mahasiswa'])->toHaveCount(1)->and($buku['Daftar Mahasiswa'][0]['NPM'])->toBe($this->mhs->npm);
});

it('membuat PDF akreditasi dengan catatan kaki LAMDIK', function () {
    ($this->buat)(2026, 'nasional');
    $pdf = app(EksporService::class)->minta(RegistriLaporan::buat('akreditasi'), 'pdf', ['prodi_id' => $this->prodi->id, 'tahun' => 2026], $this->fakultas);
    ob_start();
    $pdf->sendContent();

    expect(substr(ob_get_clean(), 0, 4))->toBe('%PDF')->and(RegistriLaporan::buat('akreditasi')->catatanKaki())->toContain('LAMDIK');
});

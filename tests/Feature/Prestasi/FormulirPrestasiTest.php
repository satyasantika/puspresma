<?php

use App\Actions\Prestasi\SimpanPrestasi;
use App\Enums\StatusPrestasi;
use App\Filament\Portal\Resources\PrestasiSayaResource;
use App\Filament\Portal\Resources\PrestasiSayaResource\Pages\CreatePrestasiSaya;
use App\Filament\Portal\Resources\PrestasiSayaResource\Pages\ListPrestasiSaya;
use App\Models\Capaian;
use App\Models\JenisKegiatan;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use App\Models\RiwayatStatusPrestasi;
use App\Models\Tingkat;
use App\Models\User;
use Database\Seeders\CapaianSeeder;
use Database\Seeders\JenisKegiatanSeeder;
use Database\Seeders\KategoriLombaSeeder;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Database\Seeders\TingkatSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, JenisKegiatanSeeder::class, KategoriLombaSeeder::class, TingkatSeeder::class, CapaianSeeder::class, PengaturanSeeder::class]);
    Filament::setCurrentPanel('portal');
    $this->mhs = Mahasiswa::factory()->create(['angkatan' => 2022]);
    $this->user = User::factory()->create(['email' => $this->mhs->email, 'prodi_id' => $this->mhs->prodi_id])->assignRole('mahasiswa');
    $this->mhs->update(['user_id' => $this->user->id]);
    $this->kompetisi = JenisKegiatan::firstWhere('kode', 'kompetisi');
});

function dataPrestasi(array $ubah = []): array
{
    $kompetisi = test()->kompetisi;
    $capaian = Capaian::where('jenis_kegiatan_id', $kompetisi->id)->firstWhere('kode', 'juara-1');

    return array_merge([
        'jenis_kegiatan_id' => $kompetisi->id,
        'bidang' => 'akademik',
        'nama_kegiatan' => 'Olimpiade Matematika Nasional 2026',
        'penyelenggara' => 'Kemdiktisaintek',
        'tanggal_mulai' => now()->subDays(10)->toDateString(),
        'tanggal_selesai' => now()->subDays(8)->toDateString(),
        'moda' => 'luring',
        'lokasi' => 'Bandung',
        'tingkat_id' => Tingkat::firstWhere('kode', 'lokal')->id,
        'capaian_id' => $capaian->id,
        'bentuk' => 'individu',
    ], $ubah);
}

it('menyimpan draf prestasi sebagai pengaju dengan anggota dan riwayat awal', function () {
    $this->actingAs($this->user);

    $prestasi = app(SimpanPrestasi::class)->jalankan(null, dataPrestasi(), $this->user);

    expect($prestasi->status)->toBe(StatusPrestasi::Draf)
        ->and($prestasi->pengaju_id)->toBe($this->mhs->id)
        ->and($prestasi->prodi_id)->toBe($this->mhs->prodi_id)
        ->and($prestasi->dibuat_oleh)->toBe($this->user->id)
        ->and($prestasi->nomor_registrasi)->toBeNull();
    $anggota = PrestasiAnggota::where('prestasi_id', $prestasi->id)->get();
    expect($anggota)->toHaveCount(1)->and($anggota->first()->is_pengaju)->toBeTrue()->and($anggota->first()->peran->value)->toBe('ketua');
    $riwayat = RiwayatStatusPrestasi::where('prestasi_id', $prestasi->id)->first();
    expect($riwayat->dari_status)->toBeNull()->and($riwayat->ke_status)->toBe(StatusPrestasi::Draf);
});

it('menyimpan draf lewat halaman buat di portal', function () {
    $this->actingAs($this->user);

    Livewire::test(CreatePrestasiSaya::class)
        ->fillForm(dataPrestasi())
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Prestasi::where('nama_kegiatan', 'Olimpiade Matematika Nasional 2026')->exists())->toBeTrue();
});

it('menolak tanggal selesai di masa depan dan tanggal selesai sebelum mulai', function () {
    $aksi = app(SimpanPrestasi::class);

    expect(fn () => $aksi->jalankan(null, dataPrestasi(['tanggal_selesai' => now()->addDay()->toDateString()]), $this->user))
        ->toThrow(ValidationException::class);
    expect(fn () => $aksi->jalankan(null, dataPrestasi(['tanggal_mulai' => now()->toDateString(), 'tanggal_selesai' => now()->subDay()->toDateString()]), $this->user))
        ->toThrow(ValidationException::class);
});

it('mewajibkan jumlah dan daftar negara untuk tingkat internasional serta jumlah PT untuk nasional', function () {
    $aksi = app(SimpanPrestasi::class);
    $internasional = Tingkat::firstWhere('kode', 'internasional')->id;
    $nasional = Tingkat::firstWhere('kode', 'nasional')->id;

    try {
        $aksi->jalankan(null, dataPrestasi(['tingkat_id' => $internasional]), $this->user);
        $this->fail('seharusnya ditolak');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKeys(['jumlah_negara_peserta', 'negara_peserta']);
    }

    expect(fn () => $aksi->jalankan(null, dataPrestasi(['tingkat_id' => $nasional]), $this->user))->toThrow(ValidationException::class);

    $ok = $aksi->jalankan(null, dataPrestasi(['tingkat_id' => $internasional, 'jumlah_negara_peserta' => 4, 'negara_peserta' => 'ID, MY, TH, SG']), $this->user);
    expect($ok->jumlah_negara_peserta)->toBe(4);
});

it('menolak capaian rekognisi untuk jenis kegiatan kompetisi', function () {
    $rekognisi = JenisKegiatan::firstWhere('kode', 'rekognisi');
    $capaianRekognisi = Capaian::where('jenis_kegiatan_id', $rekognisi->id)->firstWhere('kode', 'juri');

    expect(fn () => app(SimpanPrestasi::class)->jalankan(null, dataPrestasi(['capaian_id' => $capaianRekognisi->id]), $this->user))
        ->toThrow(ValidationException::class);
});

it('mewajibkan lokasi bila moda luring atau hibrida dan mengosongkan kolom internasional bila tidak berlaku', function () {
    expect(fn () => app(SimpanPrestasi::class)->jalankan(null, dataPrestasi(['lokasi' => '']), $this->user))->toThrow(ValidationException::class);

    $daring = app(SimpanPrestasi::class)->jalankan(null, dataPrestasi(['moda' => 'daring', 'lokasi' => '', 'jumlah_negara_peserta' => 9, 'negara_peserta' => 'x']), $this->user);
    expect($daring->lokasi)->toBeNull()->and($daring->jumlah_negara_peserta)->toBeNull();
});

it('menyaring tag HTML dari deskripsi', function () {
    $p = app(SimpanPrestasi::class)->jalankan(null, dataPrestasi(['deskripsi' => '<script>x</script>Halo <b>dunia</b>']), $this->user);

    expect($p->deskripsi)->toBe('xHalo dunia');
});

it('memberi peringatan BR-22 untuk kegiatan lama dan sebelum angkatan tanpa memblokir', function () {
    $peringatan = SimpanPrestasi::peringatan(['tanggal_selesai' => '2019-05-01'], $this->mhs);

    expect($peringatan)->toHaveCount(2);
    expect(SimpanPrestasi::peringatan(['tanggal_selesai' => now()->subDays(5)->toDateString()], $this->mhs))->toBe([]);
});

it('menampilkan hanya prestasi milik mahasiswa login di Prestasi Saya', function () {
    $milik = Prestasi::factory()->for($this->mhs, 'pengaju')->create();
    $orangLain = Prestasi::factory()->create();
    $this->actingAs($this->user);

    Livewire::test(ListPrestasiSaya::class)
        ->assertCanSeeTableRecords([$milik])
        ->assertCanNotSeeTableRecords([$orangLain]);
});

it('menolak mahasiswa lain melihat atau mengubah draf (403) dan menolak admin-prodi', function () {
    $draf = Prestasi::factory()->for($this->mhs, 'pengaju')->create();
    $lain = User::factory()->create()->assignRole('mahasiswa');
    $lain->forceFill([])->save();
    Mahasiswa::factory()->create(['user_id' => $lain->id]);

    $this->actingAs($lain)->get(PrestasiSayaResource::getUrl('view', ['record' => $draf]))->assertForbidden();
    $this->actingAs($lain)->get(PrestasiSayaResource::getUrl('edit', ['record' => $draf]))->assertForbidden();
    expect(fn () => app(SimpanPrestasi::class)->jalankan($draf, dataPrestasi(), $lain))->toThrow(AuthorizationException::class);

    $this->actingAs($this->user)->get(PrestasiSayaResource::getUrl('view', ['record' => $draf]))->assertOk();
    $this->actingAs($this->user)->get(PrestasiSayaResource::getUrl('edit', ['record' => $draf]))->assertOk();
});

it('mengizinkan hapus draf tetapi tidak prestasi yang sudah diajukan', function () {
    $draf = Prestasi::factory()->for($this->mhs, 'pengaju')->create();
    $diajukan = Prestasi::factory()->diajukan()->for($this->mhs, 'pengaju')->create();
    $this->actingAs($this->user);

    Livewire::test(ListPrestasiSaya::class)
        ->assertActionVisible(TestAction::make('delete')->table($draf))
        ->assertActionHidden(TestAction::make('delete')->table($diajukan))
        ->callAction(TestAction::make('delete')->table($draf));

    expect(Prestasi::find($draf->id))->toBeNull()->and(Prestasi::withTrashed()->find($draf->id))->not->toBeNull();
});

it('mencegah ubah data setelah diajukan dan menyembunyikan tombol edit', function () {
    $diajukan = Prestasi::factory()->diajukan()->for($this->mhs, 'pengaju')->create();
    $this->actingAs($this->user);

    Livewire::test(ListPrestasiSaya::class)->assertActionHidden(TestAction::make('edit')->table($diajukan));
    $this->get(PrestasiSayaResource::getUrl('edit', ['record' => $diajukan]))->assertForbidden();
});

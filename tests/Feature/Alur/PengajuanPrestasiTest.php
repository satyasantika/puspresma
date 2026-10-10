<?php

use App\Enums\StatusPrestasi;
use App\Filament\Portal\Resources\PrestasiSayaResource;
use App\Filament\Portal\Resources\PrestasiSayaResource\Pages\CreatePrestasiSaya;
use App\Filament\Portal\Resources\PrestasiSayaResource\Pages\ListPrestasiSaya;
use App\Models\Capaian;
use App\Models\JenisKegiatan;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\Prodi;
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
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, JenisKegiatanSeeder::class, KategoriLombaSeeder::class, TingkatSeeder::class, CapaianSeeder::class, PengaturanSeeder::class]);
    Http::fake(['*' => Http::response('', 200)]);
    Filament::setCurrentPanel('portal');
});

function mahasiswaLogin(?Prodi $prodi = null): array
{
    $m = Mahasiswa::factory()->for($prodi ?? Prodi::factory()->create())->create(['angkatan' => 2022]);
    $u = User::factory()->create(['email' => $m->email, 'prodi_id' => $m->prodi_id])->assignRole('mahasiswa');
    $m->update(['user_id' => $u->id]);

    return [$m, $u];
}

it('menjalankan alur lengkap: tim lintas prodi, tautan sertifikat Drive, ajukan, lalu anggota melihat read-only', function () {
    [$ketua, $userKetua] = mahasiswaLogin();
    [$anggota, $userAnggota] = mahasiswaLogin();
    $kompetisi = JenisKegiatan::firstWhere('kode', 'kompetisi');

    $this->actingAs($userKetua);
    Livewire::test(CreatePrestasiSaya::class)
        ->fillForm([
            'jenis_kegiatan_id' => $kompetisi->id,
            'bidang' => 'akademik',
            'nama_kegiatan' => 'Lomba Karya Tulis Ilmiah Nasional 2026',
            'penyelenggara' => 'Universitas Contoh',
            'tanggal_mulai' => now()->subDays(20)->toDateString(),
            'tanggal_selesai' => now()->subDays(18)->toDateString(),
            'moda' => 'luring',
            'lokasi' => 'Jakarta',
            'tingkat_id' => Tingkat::firstWhere('kode', 'nasional')->id,
            'jumlah_pt_peserta' => 40,
            'capaian_id' => Capaian::where('jenis_kegiatan_id', $kompetisi->id)->firstWhere('kode', 'juara-2')->id,
            'bentuk' => 'kelompok',
            'anggota_internal' => [['mahasiswa_id' => $anggota->id, 'peran' => 'anggota']],
            'anggota_eksternal' => [['nama' => 'Budi Luar', 'institusi' => 'Universitas Lain']],
            'tautan_bukti' => [['jenis' => 'sertifikat_piagam', 'url' => 'https://drive.google.com/file/d/SERT123/view?usp=sharing', 'label' => 'Sertifikat juara 2']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $prestasi = Prestasi::firstWhere('nama_kegiatan', 'Lomba Karya Tulis Ilmiah Nasional 2026');
    expect($prestasi->status)->toBe(StatusPrestasi::Draf)
        ->and($prestasi->anggota)->toHaveCount(2)
        ->and($prestasi->anggotaEksternal)->toHaveCount(1)
        ->and($prestasi->tautanBukti->first()->drive_file_id)->toBe('SERT123');

    Livewire::test(ListPrestasiSaya::class)->callAction(TestAction::make('ajukan')->table($prestasi))->assertNotified('Prestasi diajukan');
    $prestasi->refresh();
    expect($prestasi->status)->toBe(StatusPrestasi::Diajukan)->and($prestasi->nomor_registrasi)->toStartWith('PRS-'.now()->year.'-');

    $this->actingAs($userAnggota);
    Livewire::test(ListPrestasiSaya::class)
        ->assertCanSeeTableRecords([$prestasi])
        ->assertActionHidden(TestAction::make('edit')->table($prestasi))
        ->assertActionHidden(TestAction::make('ajukan')->table($prestasi))
        ->assertActionHidden(TestAction::make('tarikKembali')->table($prestasi));
    $this->get(PrestasiSayaResource::getUrl('view', ['record' => $prestasi]))->assertOk()->assertSee($prestasi->nomor_registrasi);
    $this->get(PrestasiSayaResource::getUrl('edit', ['record' => $prestasi]))->assertForbidden();
});

it('menampilkan 30 prestasi di Prestasi Saya tanpa pelanggaran lazy loading', function () {
    [$mhs, $user] = mahasiswaLogin();
    Prestasi::factory()->count(30)->for($mhs, 'pengaju')->create();
    $this->actingAs($user);

    Livewire::test(ListPrestasiSaya::class)->assertSuccessful()->assertCountTableRecords(30);
});

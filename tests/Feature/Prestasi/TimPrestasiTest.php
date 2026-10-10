<?php

use App\Actions\Prestasi\SimpanAnggotaTim;
use App\Enums\StatusKonfirmasiPembimbing;
use App\Events\AnggotaTimDitambahkan;
use App\Filament\Portal\Resources\PrestasiSayaResource;
use App\Filament\Portal\Resources\PrestasiSayaResource\Pages\ListPrestasiSaya;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use App\Models\Prodi;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    Filament::setCurrentPanel('portal');
    $this->prodiA = Prodi::factory()->create();
    $this->prodiB = Prodi::factory()->create();
    $this->pengaju = Mahasiswa::factory()->for($this->prodiA)->create();
    $this->userPengaju = User::factory()->create(['email' => $this->pengaju->email])->assignRole('mahasiswa');
    $this->pengaju->update(['user_id' => $this->userPengaju->id]);
    $this->prestasi = Prestasi::factory()->kelompok()->for($this->pengaju, 'pengaju')->create();
    $this->aksi = fn (array $internal = [], array $eksternal = [], array $pembimbing = [], ?Prestasi $p = null, ?User $oleh = null) => app(SimpanAnggotaTim::class)
        ->jalankan($p ?? $this->prestasi, $internal, $eksternal, $pembimbing, $oleh ?? $this->userPengaju);
});

it('menyimpan anggota lintas prodi dengan snapshot prodi anggota dan mengirim event', function () {
    Event::fake([AnggotaTimDitambahkan::class]);
    $b = Mahasiswa::factory()->for($this->prodiB)->create();

    ($this->aksi)([['mahasiswa_id' => $b->id, 'peran' => 'anggota']]);

    $anggota = PrestasiAnggota::where('prestasi_id', $this->prestasi->id)->get();
    expect($anggota)->toHaveCount(2)
        ->and($anggota->firstWhere('mahasiswa_id', $b->id)->prodi_id)->toBe($this->prodiB->id)
        ->and(Prestasi::melibatkanProdi($this->prodiB->id)->count())->toBe(1);
    Event::assertDispatched(AnggotaTimDitambahkan::class, fn ($e) => $e->anggota->is($b));
});

it('menolak mahasiswa ganda, pengaju sebagai anggota, mahasiswa nonaktif, dan dua ketua', function () {
    $b = Mahasiswa::factory()->create();
    $lulus = Mahasiswa::factory()->create(['status_mahasiswa' => 'lulus']);
    $c = Mahasiswa::factory()->create();

    expect(fn () => ($this->aksi)([['mahasiswa_id' => $b->id], ['mahasiswa_id' => $b->id]]))->toThrow(ValidationException::class);
    expect(fn () => ($this->aksi)([['mahasiswa_id' => $this->pengaju->id]]))->toThrow(ValidationException::class);
    expect(fn () => ($this->aksi)([['mahasiswa_id' => $lulus->id]]))->toThrow(ValidationException::class, 'tidak berstatus aktif');
    expect(fn () => ($this->aksi)([['mahasiswa_id' => $b->id, 'peran' => 'ketua'], ['mahasiswa_id' => $c->id, 'peran' => 'ketua']]))
        ->toThrow(ValidationException::class, 'satu ketua');
});

it('menolak kelompok dengan satu anggota dan menerima kombinasi internal dan eksternal', function () {
    expect(fn () => ($this->aksi)([]))->toThrow(ValidationException::class, 'minimal 2');

    ($this->aksi)([], [['nama' => 'Budi', 'institusi' => 'Fakultas Teknik Unsil', 'nomor_induk' => '123', 'program_studi' => 'Informatika']]);

    expect($this->prestasi->anggotaEksternal()->count())->toBe(1);
});

it('menolak individu dengan anggota tambahan dan menghapus anggota saat kelompok menjadi individu', function () {
    $b = Mahasiswa::factory()->create();
    ($this->aksi)([['mahasiswa_id' => $b->id]], [['nama' => 'Budi', 'institusi' => 'X']]);

    $this->prestasi->update(['bentuk' => 'individu']);
    expect(fn () => ($this->aksi)([['mahasiswa_id' => $b->id]]))->toThrow(ValidationException::class, 'hanya memiliki pengaju');

    ($this->aksi)([], []);

    expect(PrestasiAnggota::where('prestasi_id', $this->prestasi->id)->count())->toBe(1)
        ->and($this->prestasi->anggotaEksternal()->count())->toBe(0);
});

it('mempertahankan persetujuan publikasi anggota yang sudah ada dan menjadikan pengaju anggota bila ada ketua lain', function () {
    $b = Mahasiswa::factory()->create();
    ($this->aksi)([['mahasiswa_id' => $b->id, 'peran' => 'anggota']]);
    PrestasiAnggota::where('mahasiswa_id', $b->id)->update(['setuju_publikasi' => true]);

    ($this->aksi)([['mahasiswa_id' => $b->id, 'peran' => 'ketua']]);

    expect(PrestasiAnggota::firstWhere('mahasiswa_id', $b->id)->setuju_publikasi)->toBeTrue()
        ->and(PrestasiAnggota::firstWhere('mahasiswa_id', $b->id)->peran->value)->toBe('ketua')
        ->and(PrestasiAnggota::firstWhere('mahasiswa_id', $this->pengaju->id)->peran->value)->toBe('anggota');
});

it('mengizinkan anggota non-pengaju melihat prestasi di daftar tetapi tidak mengubahnya', function () {
    $b = Mahasiswa::factory()->for($this->prodiB)->create();
    $userB = User::factory()->create(['email' => $b->email])->assignRole('mahasiswa');
    $b->update(['user_id' => $userB->id]);
    ($this->aksi)([['mahasiswa_id' => $b->id]]);

    $this->actingAs($userB);
    Livewire::test(ListPrestasiSaya::class)->assertCanSeeTableRecords([$this->prestasi]);
    $this->get(PrestasiSayaResource::getUrl('view', ['record' => $this->prestasi]))->assertOk();
    $this->get(PrestasiSayaResource::getUrl('edit', ['record' => $this->prestasi]))->assertForbidden();
    expect(fn () => ($this->aksi)([], [], [], null, $userB))->toThrow(AuthorizationException::class);
});

it('menyimpan dosen pembimbing atau nama pembimbing lain dan mereset konfirmasi saat berganti', function () {
    $d1 = Dosen::factory()->create();
    $d2 = Dosen::factory()->create();
    $b = Mahasiswa::factory()->create();
    $internal = [['mahasiswa_id' => $b->id]];

    ($this->aksi)($internal, [], ['dosen_pembimbing_id' => $d1->id, 'nama_pembimbing_lain' => 'Abaikan']);
    $this->prestasi->refresh();
    expect($this->prestasi->dosen_pembimbing_id)->toBe($d1->id)->and($this->prestasi->nama_pembimbing_lain)->toBeNull();

    $this->prestasi->update(['status_konfirmasi_pembimbing' => StatusKonfirmasiPembimbing::Dikonfirmasi]);
    ($this->aksi)($internal, [], ['dosen_pembimbing_id' => $d1->id]);
    expect($this->prestasi->refresh()->status_konfirmasi_pembimbing)->toBe(StatusKonfirmasiPembimbing::Dikonfirmasi);

    ($this->aksi)($internal, [], ['dosen_pembimbing_id' => $d2->id]);
    expect($this->prestasi->refresh()->status_konfirmasi_pembimbing)->toBe(StatusKonfirmasiPembimbing::TidakAda);

    ($this->aksi)($internal, [], ['nama_pembimbing_lain' => 'Pak Luar']);
    expect($this->prestasi->refresh()->dosen_pembimbing_id)->toBeNull()->and($this->prestasi->nama_pembimbing_lain)->toBe('Pak Luar');
});

it('menolak dosen pembimbing yang tidak ada di master', function () {
    $b = Mahasiswa::factory()->create();

    expect(fn () => ($this->aksi)([['mahasiswa_id' => $b->id]], [], ['dosen_pembimbing_id' => '00000000-0000-7000-8000-000000000000']))
        ->toThrow(ValidationException::class);
});

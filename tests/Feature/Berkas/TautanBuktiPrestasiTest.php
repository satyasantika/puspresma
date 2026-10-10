<?php

use App\Actions\Bukti\HapusTautanBukti;
use App\Actions\Bukti\SimpanTautanBukti;
use App\Actions\Bukti\UbahTautanBukti;
use App\Enums\JenisTautan;
use App\Filament\Portal\Resources\PrestasiSayaResource;
use App\Models\Aktivitas;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\Prodi;
use App\Models\TautanBerkas;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    Http::fake(['*' => Http::response('', 200)]);
    Filament::setCurrentPanel('portal');
    $this->prodiA = Prodi::factory()->create();
    $this->pengaju = Mahasiswa::factory()->for($this->prodiA)->create();
    $this->user = User::factory()->create(['email' => $this->pengaju->email, 'prodi_id' => $this->prodiA->id])->assignRole('mahasiswa');
    $this->pengaju->update(['user_id' => $this->user->id]);
    $this->prestasi = Prestasi::factory()->for($this->pengaju, 'pengaju')->create();
    $this->simpan = fn (string $url, JenisTautan $jenis = JenisTautan::SertifikatPiagam, bool $publik = false, ?Prestasi $p = null, ?User $oleh = null) => app(SimpanTautanBukti::class)
        ->jalankan($p ?? $this->prestasi, $jenis, $url, 'Label', $publik, $oleh ?? $this->user);
});

it('menolak http dan folder Drive serta menyimpan drive_file_id', function () {
    expect(fn () => ($this->simpan)('http://drive.google.com/file/d/ABC/view'))->toThrow(ValidationException::class);
    expect(fn () => ($this->simpan)('https://drive.google.com/drive/folders/XYZ'))->toThrow(ValidationException::class, 'bukan folder');

    $tautan = ($this->simpan)('https://drive.google.com/file/d/ABC123/view?usp=sharing');

    expect($tautan->drive_file_id)->toBe('ABC123')
        ->and($tautan->pemilik_type)->toBe('prestasi')
        ->and($tautan->ditambahkan_oleh)->toBe($this->user->id);
});

it('mengabaikan tampil_publik pada sertifikat dan menyimpannya hanya pada foto', function () {
    $sertifikat = ($this->simpan)('https://drive.google.com/file/d/S1/view', JenisTautan::SertifikatPiagam, true);
    $foto = ($this->simpan)('https://drive.google.com/file/d/F1/view', JenisTautan::FotoPenyerahan, true);

    expect($sertifikat->refresh()->tampil_publik)->toBeFalse()->and($foto->refresh()->tampil_publik)->toBeTrue();
});

it('menolak tautan ke-11, jenis poster, dan penambahan saat terverifikasi', function () {
    foreach (range(1, 10) as $i) {
        ($this->simpan)("https://drive.google.com/file/d/ID{$i}/view");
    }
    expect(fn () => ($this->simpan)('https://drive.google.com/file/d/ID11/view'))->toThrow(ValidationException::class, 'Maksimal 10');

    $baru = Prestasi::factory()->for($this->pengaju, 'pengaju')->create();
    expect(fn () => ($this->simpan)('https://drive.google.com/file/d/P/view', JenisTautan::Poster, false, $baru))->toThrow(ValidationException::class);

    $terkunci = Prestasi::factory()->terverifikasi()->for($this->pengaju, 'pengaju')->create();
    expect(fn () => ($this->simpan)('https://drive.google.com/file/d/T/view', JenisTautan::SertifikatPiagam, false, $terkunci))->toThrow(AuthorizationException::class);
});

it('menolak mahasiswa lain menambah tautan pada prestasi orang lain', function () {
    $lain = User::factory()->create()->assignRole('mahasiswa');
    Mahasiswa::factory()->create(['user_id' => $lain->id]);

    expect(fn () => ($this->simpan)('https://drive.google.com/file/d/ABC/view', JenisTautan::SertifikatPiagam, false, null, $lain))->toThrow(AuthorizationException::class);
});

it('mencatat perubahan url lama ke baru dan mengizinkan hapus selama draf', function () {
    $tautan = ($this->simpan)('https://drive.google.com/file/d/ABC/view');

    app(UbahTautanBukti::class)->jalankan($tautan, 'https://drive.google.com/file/d/NEW/view', 'Baru', false, $this->user);

    $log = Aktivitas::where('log_name', 'tautan')->where('event', 'updated')->latest()->first();
    expect($log->attribute_changes['old']['url'])->toBe('https://drive.google.com/file/d/ABC/view')
        ->and($log->attribute_changes['attributes']['url'])->toBe('https://drive.google.com/file/d/NEW/view');

    expect(fn () => app(UbahTautanBukti::class)->jalankan($tautan, 'http://x.com/a', null, false, $this->user))->toThrow(ValidationException::class);

    app(HapusTautanBukti::class)->jalankan($tautan, $this->user);
    expect(TautanBerkas::find($tautan->id))->toBeNull();

    $this->prestasi->forceFill(['status' => 'diajukan'])->save();
    $lain = ($this->simpan)('https://drive.google.com/file/d/X/view', JenisTautan::SertifikatPiagam, false, Prestasi::factory()->for($this->pengaju, 'pengaju')->create());
    $lain->pemilik->forceFill(['status' => 'terverifikasi'])->save();
    expect(fn () => app(HapusTautanBukti::class)->jalankan($lain->refresh(), $this->user))->toThrow(AuthorizationException::class);
});

it('mengatur akses route tautan.buka menurut peran (IDOR)', function () {
    $tautan = ($this->simpan)('https://drive.google.com/file/d/ABC/view');
    $url = route('tautan.buka', $tautan);

    // pengaju dan admin-fakultas boleh
    $this->actingAs($this->user)->get($url)->assertRedirect($tautan->url);
    $this->actingAs(User::factory()->create()->assignRole('admin-fakultas'))->get($url)->assertRedirect($tautan->url);

    // mahasiswa lain, admin-prodi prodi lain, dan pimpinan: 403
    $lain = User::factory()->create()->assignRole('mahasiswa');
    Mahasiswa::factory()->create(['user_id' => $lain->id]);
    $this->actingAs($lain)->get($url)->assertForbidden();
    $this->actingAs(User::factory()->create(['prodi_id' => Prodi::factory()->create()->id])->assignRole('admin-prodi'))->get($url)->assertForbidden();
    $this->actingAs(User::factory()->create()->assignRole('pimpinan'))->get($url)->assertForbidden();

    // admin-prodi dari prodi anggota: 302
    $this->actingAs(User::factory()->create(['prodi_id' => $this->prodiA->id])->assignRole('admin-prodi'))->get($url)->assertRedirect($tautan->url);
});

it('mengizinkan dosen pembimbing membuka tautan bimbingannya saja', function () {
    $dosen = Dosen::factory()->create();
    $userDosen = User::factory()->create()->assignRole('dosen');
    $dosen->update(['user_id' => $userDosen->id]);
    $tautan = ($this->simpan)('https://drive.google.com/file/d/ABC/view');

    $this->actingAs($userDosen)->get(route('tautan.buka', $tautan))->assertForbidden();

    $this->prestasi->update(['dosen_pembimbing_id' => $dosen->id]);
    $this->actingAs($userDosen)->get(route('tautan.buka', $tautan))->assertRedirect($tautan->url);
});

it('menampilkan tautan dan pratinjau pada halaman lihat prestasi bagi anggota', function () {
    $tautan = ($this->simpan)('https://drive.google.com/file/d/ABC/view');

    $this->actingAs($this->user)->get(PrestasiSayaResource::getUrl('view', ['record' => $this->prestasi]))
        ->assertOk()->assertSee(route('tautan.buka', $tautan), false)->assertSee('https://drive.google.com/file/d/ABC/preview', false);
});

it('tidak menyimpan berkas apa pun di storage saat tautan disimpan', function () {
    $sebelum = Storage::disk('local')->allFiles();
    $sebelumPublik = Storage::disk('public')->allFiles();

    ($this->simpan)('https://drive.google.com/file/d/ABC/view');

    expect(Storage::disk('local')->allFiles())->toBe($sebelum)->and(Storage::disk('public')->allFiles())->toBe($sebelumPublik);
});

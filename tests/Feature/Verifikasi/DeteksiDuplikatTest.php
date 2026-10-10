<?php

use App\Actions\Duplikat\TinjauKandidatDuplikat;
use App\Actions\Prestasi\AjukanPrestasi;
use App\Actions\Prestasi\VerifikasiPrestasi;
use App\Enums\AlasanDuplikat;
use App\Enums\StatusTinjauDuplikat;
use App\Jobs\DeteksiDuplikatPrestasi;
use App\Models\KandidatDuplikat;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use App\Models\TautanBerkas;
use App\Models\User;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class]);
    Http::fake(['*' => Http::response('', 200)]);
    $this->ketua = Mahasiswa::factory()->create();
    $this->anggota = Mahasiswa::factory()->create();
    $this->fakultas = User::factory()->create()->assignRole('admin-fakultas');
    $this->buat = function (string $nama, string $mulai, ?Mahasiswa $ketua = null, bool $denganAnggota = true): Prestasi {
        $p = Prestasi::factory()->kelompok()->for($ketua ?? $this->ketua, 'pengaju')->create(['nama_kegiatan' => $nama, 'tanggal_mulai' => $mulai, 'tanggal_selesai' => $mulai]);
        if ($denganAnggota) {
            PrestasiAnggota::factory()->create(['prestasi_id' => $p->id, 'mahasiswa_id' => $this->anggota->id]);
        }
        TautanBerkas::factory()->untukPrestasi($p)->sertifikat()->create();

        return $p;
    };
});

it('membentuk kandidat untuk tim sama dengan nama mirip dan tanggal berdekatan saat diajukan', function () {
    $lama = ($this->buat)('Lomba Debat Nasional 2026', '2026-03-10');
    $lama->forceFill(['status' => 'diajukan'])->save();
    $baru = ($this->buat)('Debat Nasional 2026', '2026-03-11');

    app(AjukanPrestasi::class)->jalankan($baru, $this->fakultas);

    $kandidat = KandidatDuplikat::where('prestasi_id', $baru->id)->get();
    expect($kandidat)->toHaveCount(1)
        ->and($kandidat->first()->pembanding_id)->toBe($lama->id)
        ->and($kandidat->first()->alasan)->toBe(AlasanDuplikat::KegiatanMirip)
        ->and($kandidat->first()->skor)->toBe('100.00')
        ->and($kandidat->first()->status_tinjau)->toBe(StatusTinjauDuplikat::BelumDitinjau);
});

it('tidak membentuk kandidat bila nama berbeda jauh, anggota berbeda, atau tanggal jauh', function () {
    $lama = ($this->buat)('Lomba Debat Nasional 2026', '2026-03-10');
    $lama->forceFill(['status' => 'diajukan'])->save();

    $beda = ($this->buat)('Olimpiade Fisika Dasar', '2026-03-10');
    $lain = Prestasi::factory()->for(Mahasiswa::factory(), 'pengaju')->create(['nama_kegiatan' => 'Debat Nasional 2026', 'tanggal_mulai' => '2026-03-10', 'tanggal_selesai' => '2026-03-10']);
    $jauh = ($this->buat)('Debat Nasional 2026', '2026-06-10');

    foreach ([$beda, $lain, $jauh] as $p) {
        $p->forceFill(['status' => 'diajukan'])->save();
        DeteksiDuplikatPrestasi::dispatchSync($p);
    }

    expect(KandidatDuplikat::count())->toBe(0);
});

it('membentuk kandidat tautan_sama walau parameter URL berbeda dan tanpa anggota yang sama', function () {
    $lama = Prestasi::factory()->diajukan()->create();
    TautanBerkas::factory()->untukPrestasi($lama)->create(['url' => 'https://drive.google.com/file/d/SAMA999/view?usp=sharing']);
    $baru = Prestasi::factory()->create();
    TautanBerkas::factory()->untukPrestasi($baru)->create(['url' => 'https://drive.google.com/file/d/SAMA999/preview']);
    $baru->forceFill(['status' => 'diajukan'])->save();

    DeteksiDuplikatPrestasi::dispatchSync($baru);

    $kandidat = KandidatDuplikat::where('prestasi_id', $baru->id)->first();
    expect($kandidat->alasan)->toBe(AlasanDuplikat::TautanSama)->and($kandidat->pembanding_id)->toBe($lama->id)->and($kandidat->skor)->toBe('100.00');
});

it('mencocokkan url_normal untuk penyedia non-Drive dan mengabaikan pembanding berstatus draf', function () {
    $lama = Prestasi::factory()->diajukan()->create();
    TautanBerkas::factory()->untukPrestasi($lama)->create(['url' => 'https://fkip.unsil.ac.id/sertifikat/a.pdf?utm_source=wa']);
    $draf = Prestasi::factory()->create();
    TautanBerkas::factory()->untukPrestasi($draf)->create(['url' => 'https://fkip.unsil.ac.id/sertifikat/a.pdf']);
    $baru = Prestasi::factory()->diajukan()->create();
    TautanBerkas::factory()->untukPrestasi($baru)->create(['url' => 'https://FKIP.unsil.ac.id/sertifikat/a.pdf']);

    DeteksiDuplikatPrestasi::dispatchSync($baru);

    expect(KandidatDuplikat::where('prestasi_id', $baru->id)->pluck('pembanding_id')->all())->toBe([$lama->id]);
});

it('tidak menggandakan baris saat job dijalankan dua kali dan tidak menimpa status tinjau', function () {
    $lama = ($this->buat)('Debat Nasional 2026', '2026-03-10');
    $lama->forceFill(['status' => 'diajukan'])->save();
    $baru = ($this->buat)('Debat Nasional 2026', '2026-03-10');
    $baru->forceFill(['status' => 'diajukan'])->save();

    DeteksiDuplikatPrestasi::dispatchSync($baru);
    $kandidat = KandidatDuplikat::first();
    app(TinjauKandidatDuplikat::class)->jalankan($kandidat, $this->fakultas, StatusTinjauDuplikat::BukanDuplikat);
    DeteksiDuplikatPrestasi::dispatchSync($baru);

    expect(KandidatDuplikat::count())->toBe(1)->and($kandidat->refresh()->status_tinjau)->toBe(StatusTinjauDuplikat::BukanDuplikat);
});

it('memblokir verifikasi sampai kandidat ditinjau bukan_duplikat', function () {
    $lama = ($this->buat)('Debat Nasional 2026', '2026-03-10');
    $lama->forceFill(['status' => 'diajukan'])->save();
    $baru = ($this->buat)('Debat Nasional 2026', '2026-03-10');
    app(AjukanPrestasi::class)->jalankan($baru, $this->fakultas);

    expect(fn () => app(VerifikasiPrestasi::class)->jalankan($baru->refresh(), $this->fakultas))
        ->toThrow(ValidationException::class, 'Tinjau kandidat duplikat terlebih dahulu');

    app(TinjauKandidatDuplikat::class)->jalankan(KandidatDuplikat::first(), $this->fakultas, StatusTinjauDuplikat::BukanDuplikat);
    app(VerifikasiPrestasi::class)->jalankan($baru->refresh(), $this->fakultas);

    expect($baru->refresh()->status->value)->toBe('terverifikasi');
});

it('mewajibkan catatan untuk duplikat dan menolak peninjau tanpa izin atau super-admin', function () {
    $lama = Prestasi::factory()->diajukan()->create();
    $kandidat = KandidatDuplikat::create(['prestasi_id' => Prestasi::factory()->diajukan()->create()->id, 'pembanding_id' => $lama->id, 'alasan' => 'tautan_sama', 'skor' => '100.00']);
    $aksi = app(TinjauKandidatDuplikat::class);

    expect(fn () => $aksi->jalankan($kandidat, $this->fakultas, StatusTinjauDuplikat::Duplikat, 'singkat'))->toThrow(ValidationException::class);
    expect(fn () => $aksi->jalankan($kandidat, User::factory()->create()->assignRole('admin-prodi'), StatusTinjauDuplikat::BukanDuplikat))->toThrow(AuthorizationException::class);
    expect(fn () => $aksi->jalankan($kandidat, User::factory()->create()->assignRole('super-admin'), StatusTinjauDuplikat::BukanDuplikat))->toThrow(AuthorizationException::class);

    $aksi->jalankan($kandidat, $this->fakultas, StatusTinjauDuplikat::Duplikat, 'Sama persis dengan prestasi sebelumnya.');
    expect($kandidat->refresh()->status_tinjau)->toBe(StatusTinjauDuplikat::Duplikat)->and($kandidat->ditinjau_oleh)->toBe($this->fakultas->id);
});

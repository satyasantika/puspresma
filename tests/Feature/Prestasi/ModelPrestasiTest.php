<?php

use App\Enums\StatusPrestasi;
use App\Models\Aktivitas;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use App\Models\Prodi;
use App\Models\TautanBerkas;
use App\Services\NormalisasiTeks;
use Illuminate\Support\Facades\Schema;

it('menormalkan nama kegiatan dan mengisi tahun dari tanggal selesai', function () {
    $prestasi = Prestasi::factory()->create(['nama_kegiatan' => 'Lomba Debat Nasional 2026!', 'tanggal_mulai' => '2026-03-01', 'tanggal_selesai' => '2026-03-03']);

    expect($prestasi->nama_kegiatan_normal)->toBe('debat')
        ->and($prestasi->tahun)->toBe(2026)
        ->and($prestasi->id)->toBeString()->not->toBeEmpty();

    $prestasi->update(['nama_kegiatan' => 'Kompetisi Olimpiade Matematika Tingkat Wilayah 2025', 'tanggal_selesai' => '2027-01-01']);
    expect($prestasi->nama_kegiatan_normal)->toBe('olimpiade matematika wilayah')->and($prestasi->tahun)->toBe(2027);
});

it('merapikan spasi dan tanda baca pada normalisasi', function () {
    expect(NormalisasiTeks::kegiatan('  Kompetisi   Karya, Tulis -- Ilmiah!!  '))->toBe('karya tulis ilmiah');
});

it('menemukan prestasi lintas prodi lewat scope melibatkanProdi dan milikMahasiswa', function () {
    $a = Prodi::factory()->create();
    $b = Prodi::factory()->create();
    $prestasi = Prestasi::factory()->kelompok()->for(Mahasiswa::factory()->for($a), 'pengaju')->create();
    $anggotaB = Mahasiswa::factory()->for($b)->create();
    PrestasiAnggota::factory()->create(['prestasi_id' => $prestasi->id, 'mahasiswa_id' => $anggotaB->id]);
    Prestasi::factory()->for(Mahasiswa::factory()->for(Prodi::factory()), 'pengaju')->create();

    expect(Prestasi::melibatkanProdi($a->id)->pluck('id')->all())->toBe([$prestasi->id])
        ->and(Prestasi::melibatkanProdi($b->id)->pluck('id')->all())->toBe([$prestasi->id])
        ->and(Prestasi::milikMahasiswa($anggotaB->id)->count())->toBe(1)
        ->and($prestasi->mahasiswa)->toHaveCount(2)
        ->and($prestasi->anggota->firstWhere('mahasiswa_id', $anggotaB->id)->prodi_id)->toBe($b->id);
});

it('menyaring berdasarkan status dan terverifikasi', function () {
    Prestasi::factory()->create();
    Prestasi::factory()->diajukan()->create();
    Prestasi::factory()->terverifikasi()->create();

    expect(Prestasi::status(StatusPrestasi::Draf)->count())->toBe(1)
        ->and(Prestasi::status(StatusPrestasi::Draf, StatusPrestasi::Diajukan)->count())->toBe(2)
        ->and(Prestasi::terverifikasi()->count())->toBe(1);
});

it('menjadikan pengaju anggota otomatis lewat factory dan memetakan tautan bukti tanpa poster', function () {
    $prestasi = Prestasi::factory()->create();
    TautanBerkas::factory()->untukPrestasi($prestasi)->sertifikat()->create();
    TautanBerkas::factory()->untukPrestasi($prestasi)->fotoPublik()->create();
    TautanBerkas::factory()->untukPrestasi($prestasi)->create(['jenis' => 'poster']);

    expect($prestasi->anggota->where('is_pengaju', true))->toHaveCount(1)
        ->and($prestasi->tautanBukti)->toHaveCount(2)
        ->and($prestasi->tautanBukti->firstWhere('jenis.value', 'foto_penyerahan')->status_cek->value)->toBe('dapat_diakses')
        ->and(TautanBerkas::first()->pemilik_type)->toBe('prestasi');
});

it('mencatat perubahan prestasi di activitylog tanpa kolom turunan', function () {
    $prestasi = Prestasi::factory()->create();
    $prestasi->update(['penyelenggara' => 'Penyelenggara Baru']);

    $log = Aktivitas::where('log_name', 'prestasi')->where('event', 'updated')->latest()->first();
    expect($log->attribute_changes['attributes'])->toHaveKey('penyelenggara')->not->toHaveKey('nama_kegiatan_normal');
});

it('memiliki indeks fulltext dan tidak memiliki tabel penyimpan berkas', function () {
    $indeks = collect(Schema::getIndexes('prestasi'))->firstWhere(fn ($i) => $i['columns'] === ['nama_kegiatan', 'penyelenggara']);

    expect($indeks['type'])->toBe('fulltext')
        ->and(Schema::hasTable('berkas_bukti'))->toBeFalse()
        ->and(Schema::hasTable('prestasi_anggota_eksternal'))->toBeTrue()
        ->and(Schema::hasTable('riwayat_status_prestasi'))->toBeTrue();
});

it('menegakkan transisi status yang sah pada enum (BR-07)', function () {
    expect(StatusPrestasi::Draf->bolehKe(StatusPrestasi::Diajukan))->toBeTrue()
        ->and(StatusPrestasi::Draf->bolehKe(StatusPrestasi::Terverifikasi))->toBeFalse()
        ->and(StatusPrestasi::Terverifikasi->bolehKe(StatusPrestasi::PerluPerbaikan))->toBeTrue()
        ->and(StatusPrestasi::Ditolak->tujuanSah())->toBe([])
        ->and(StatusPrestasi::Draf->bolehDiubahMahasiswa())->toBeTrue()
        ->and(StatusPrestasi::Diajukan->bolehDiubahMahasiswa())->toBeFalse();
});

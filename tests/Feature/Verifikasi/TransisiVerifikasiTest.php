<?php

use App\Actions\Prestasi\AjukanPrestasi;
use App\Actions\Prestasi\BukaVerifikasiPrestasi;
use App\Actions\Prestasi\KembalikanUntukPerbaikan;
use App\Actions\Prestasi\LoloskanTahapProdi;
use App\Actions\Prestasi\TolakPrestasi;
use App\Actions\Prestasi\TransisiStatus;
use App\Actions\Prestasi\VerifikasiPrestasi;
use App\Enums\StatusKonfirmasiPembimbing;
use App\Enums\StatusPrestasi;
use App\Exceptions\TransisiTidakSah;
use App\Models\Aktivitas;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use App\Models\Prodi;
use App\Models\RiwayatStatusPrestasi;
use App\Models\SnapshotTautanVerifikasi;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Services\Pengaturan;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class]);
    Http::fake(['*' => Http::response('', 200)]);
    $this->prodi = Prodi::factory()->create();
    $this->prestasi = Prestasi::factory()->diajukan()->for(Mahasiswa::factory()->for($this->prodi), 'pengaju')->create();
    TautanBerkas::factory()->untukPrestasi($this->prestasi)->sertifikat()->create();
    $this->fakultas = User::factory()->create()->assignRole('admin-fakultas');
    $this->adminProdi = User::factory()->create(['prodi_id' => $this->prodi->id])->assignRole('admin-prodi');
});

function muatUlang(): Prestasi
{
    return Prestasi::find(test()->prestasi->id);
}

it('memverifikasi prestasi, menulis riwayat, snapshot, dan menaikkan versi cache', function () {
    Cache::forget('statistik:versi');
    Cache::forget('galeri:versi');

    app(VerifikasiPrestasi::class)->jalankan($this->prestasi, $this->fakultas, 'Data sesuai sertifikat.');

    $p = muatUlang();
    expect($p->status)->toBe(StatusPrestasi::Terverifikasi)
        ->and($p->diverifikasi_oleh)->toBe($this->fakultas->id)
        ->and($p->diverifikasi_at)->not->toBeNull()
        ->and(RiwayatStatusPrestasi::where('prestasi_id', $p->id)->latest('created_at')->first()->ke_status)->toBe(StatusPrestasi::Terverifikasi)
        ->and(SnapshotTautanVerifikasi::where('prestasi_id', $p->id)->count())->toBe(1)
        ->and(Cache::get('statistik:versi'))->toBe(1)->and(Cache::get('galeri:versi'))->toBe(1);
});

it('menolak verifikasi tanpa membuka tautan, oleh admin-prodi, dan oleh super-admin', function () {
    $super = User::factory()->create()->assignRole('super-admin');
    $aksi = app(VerifikasiPrestasi::class);

    expect(fn () => $aksi->jalankan($this->prestasi, $this->fakultas, null, false, false, false))->toThrow(ValidationException::class, 'membuka');
    expect(fn () => $aksi->jalankan($this->prestasi, $this->adminProdi))->toThrow(AuthorizationException::class);
    expect(fn () => $aksi->jalankan($this->prestasi, $super))->toThrow(AuthorizationException::class);
    expect(muatUlang()->status)->toBe(StatusPrestasi::Diajukan);
});

it('menjalankan tahap prodi: loloskan, lalu verifikasi final dari lolos_prodi', function () {
    Pengaturan::setel('verifikasi_prodi_aktif', true);

    app(LoloskanTahapProdi::class)->jalankan($this->prestasi, $this->adminProdi);
    expect(muatUlang()->status)->toBe(StatusPrestasi::LolosProdi);

    app(VerifikasiPrestasi::class)->jalankan(muatUlang(), $this->fakultas);
    expect(muatUlang()->status)->toBe(StatusPrestasi::Terverifikasi);
});

it('menolak loloskan oleh admin-prodi prodi lain atau saat tahap prodi nonaktif', function () {
    $lain = User::factory()->create(['prodi_id' => Prodi::factory()->create()->id])->assignRole('admin-prodi');

    expect(fn () => app(LoloskanTahapProdi::class)->jalankan($this->prestasi, $this->adminProdi))->toThrow(AuthorizationException::class);

    Pengaturan::setel('verifikasi_prodi_aktif', true);
    expect(fn () => app(LoloskanTahapProdi::class)->jalankan($this->prestasi, $lain))->toThrow(AuthorizationException::class);
});

it('mewajibkan ambil alih dan catatan saat verifikasi dari diajukan dengan tahap prodi aktif', function () {
    Pengaturan::setel('verifikasi_prodi_aktif', true);
    $aksi = app(VerifikasiPrestasi::class);

    expect(fn () => $aksi->jalankan($this->prestasi, $this->fakultas))->toThrow(ValidationException::class, 'ambil alih');
    expect(fn () => $aksi->jalankan($this->prestasi, $this->fakultas, 'pendek', true))->toThrow(ValidationException::class);

    $aksi->jalankan($this->prestasi, $this->fakultas, 'Diambil alih karena mendesak.', true);
    expect(muatUlang()->status)->toBe(StatusPrestasi::Terverifikasi);
});

it('mengembalikan dan menolak dengan catatan wajib; ditolak bersifat final', function () {
    expect(fn () => app(KembalikanUntukPerbaikan::class)->jalankan($this->prestasi, $this->fakultas, ''))->toThrow(ValidationException::class);
    expect(fn () => app(TolakPrestasi::class)->jalankan($this->prestasi, $this->fakultas, 'singkat'))->toThrow(ValidationException::class);

    app(KembalikanUntukPerbaikan::class)->jalankan($this->prestasi, $this->fakultas, 'Tautan sertifikat tidak terbaca.');
    expect(muatUlang()->status)->toBe(StatusPrestasi::PerluPerbaikan)->and(muatUlang()->catatan_verifikasi)->toBe('Tautan sertifikat tidak terbaca.');

    $lain = Prestasi::factory()->diajukan()->create();
    app(TolakPrestasi::class)->jalankan($lain, $this->fakultas, 'Bukan prestasi yang valid.');
    expect($lain->refresh()->status)->toBe(StatusPrestasi::Ditolak);
    expect(fn () => app(TransisiStatus::class)->ubah($lain, StatusPrestasi::Diajukan, $this->fakultas))->toThrow(TransisiTidakSah::class);
    expect(fn () => app(VerifikasiPrestasi::class)->jalankan($lain, $this->fakultas))->toThrow(AuthorizationException::class);
});

it('mengizinkan admin-prodi mengembalikan hanya pada tahap prodi aktif untuk prodinya', function () {
    expect(fn () => app(KembalikanUntukPerbaikan::class)->jalankan($this->prestasi, $this->adminProdi, 'Mohon lengkapi sertifikat.'))->toThrow(AuthorizationException::class);

    Pengaturan::setel('verifikasi_prodi_aktif', true);
    app(KembalikanUntukPerbaikan::class)->jalankan($this->prestasi, $this->adminProdi, 'Mohon lengkapi sertifikat.');

    expect(muatUlang()->status)->toBe(StatusPrestasi::PerluPerbaikan);
});

it('memblokir pembimbing yang menyangkal dan mensyaratkan centang bila masih menunggu', function () {
    $this->prestasi->forceFill(['dosen_pembimbing_id' => Dosen::factory()->create()->id, 'status_konfirmasi_pembimbing' => StatusKonfirmasiPembimbing::Menunggu])->save();
    $aksi = app(VerifikasiPrestasi::class);

    expect(fn () => $aksi->jalankan(muatUlang(), $this->fakultas))->toThrow(ValidationException::class, 'tanpa konfirmasi');

    $aksi->jalankan(muatUlang(), $this->fakultas, 'Pembimbing belum menjawab, data dicek manual.', false, true);
    expect(muatUlang()->verifikasi_tanpa_konfirmasi)->toBeTrue();

    $lain = Prestasi::factory()->diajukan()->create(['dosen_pembimbing_id' => Dosen::factory()->create()->id, 'status_konfirmasi_pembimbing' => StatusKonfirmasiPembimbing::Disangkal]);
    expect(fn () => $aksi->jalankan($lain, $this->fakultas, 'Catatan yang cukup panjang.', false, true))->toThrow(ValidationException::class, 'menyangkal');
});

it('membuka verifikasi: kembali ke perlu_perbaikan, poin dikosongkan, snapshot lama tetap, verifikasi kedua menambah snapshot', function () {
    PrestasiAnggota::where('prestasi_id', $this->prestasi->id)->update(['poin' => '40.00']);
    app(VerifikasiPrestasi::class)->jalankan($this->prestasi, $this->fakultas);
    expect(SnapshotTautanVerifikasi::count())->toBe(1);

    expect(fn () => app(BukaVerifikasiPrestasi::class)->jalankan(muatUlang(), $this->fakultas, 'kurang'))->toThrow(ValidationException::class);
    app(BukaVerifikasiPrestasi::class)->jalankan(muatUlang(), $this->fakultas, 'Tingkat kegiatan keliru, perlu dikoreksi.');

    expect(muatUlang()->status)->toBe(StatusPrestasi::PerluPerbaikan)
        ->and(PrestasiAnggota::where('prestasi_id', $this->prestasi->id)->whereNotNull('poin')->count())->toBe(0)
        ->and(SnapshotTautanVerifikasi::count())->toBe(1);

    $siapAjukan = muatUlang();
    app(AjukanPrestasi::class)->jalankan($siapAjukan, $this->fakultas);
    app(VerifikasiPrestasi::class)->jalankan(muatUlang(), $this->fakultas);
    expect(SnapshotTautanVerifikasi::count())->toBe(2);
});

it('menjaga snapshot tidak berubah walau tautan diubah kemudian dan tidak dapat diubah/dihapus', function () {
    app(VerifikasiPrestasi::class)->jalankan($this->prestasi, $this->fakultas);
    $snapshot = SnapshotTautanVerifikasi::first();
    $urlLama = $snapshot->url;

    $tautan = $this->prestasi->tautanBukti()->first();
    $tautan->update(['url' => 'https://drive.google.com/file/d/BARU/view']);

    expect($snapshot->refresh()->url)->toBe($urlLama);
    expect(fn () => $snapshot->update(['url' => 'https://x.test']))->toThrow(LogicException::class);
    expect(fn () => $snapshot->delete())->toThrow(LogicException::class);
});

it('mencatat dibuka_verifikator pada snapshot dari activitylog tautan.buka', function () {
    $tautan = $this->prestasi->tautanBukti()->first();
    $this->actingAs($this->fakultas)->get(route('tautan.buka', $tautan))->assertRedirect();
    expect(Aktivitas::where('event', 'dibuka')->count())->toBe(1);

    app(VerifikasiPrestasi::class)->jalankan($this->prestasi, $this->fakultas);

    expect(SnapshotTautanVerifikasi::first()->dibuka_verifikator)->toBeTrue();
});

it('menolak verifikasi pada status yang tidak sah dan tidak mengubah status di luar Action', function () {
    $draf = Prestasi::factory()->create();

    expect(fn () => app(VerifikasiPrestasi::class)->jalankan($draf, $this->fakultas))->toThrow(AuthorizationException::class);
    expect(fn () => app(BukaVerifikasiPrestasi::class)->jalankan($this->prestasi, $this->fakultas, 'Catatan buka verifikasi.'))->toThrow(AuthorizationException::class);
});

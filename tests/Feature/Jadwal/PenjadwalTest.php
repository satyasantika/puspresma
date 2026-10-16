<?php

use App\Jobs\KirimPengingatTenggatKompetisi;
use App\Jobs\PeriksaTautanBerkas;
use App\Models\Dosen;
use App\Models\Kompetisi;
use App\Models\KompetisiMinat;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\Prodi;
use App\Models\RiwayatImpor;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Notifications\PengingatKonfirmasiPembimbing;
use App\Notifications\PengingatTenggatKompetisi;
use App\Notifications\RingkasanAntreanVerifikasi;
use App\Services\Pengaturan;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class]);
    Http::fake(['*' => Http::response('', 200)]);
});

it('mendaftarkan semua perintah terjadwal dengan lock satu server dan tanpa tumpang tindih', function () {
    $acara = collect(app(Schedule::class)->events());
    $deskripsi = $acara->map(fn ($e) => $e->command ?? $e->description ?? '')->implode("\n");

    foreach (['queue:work database --queue=default', 'queue:work database-berat --queue=berat', 'detak-penjadwal', 'queue:prune-batches', 'puspresma:arsipkan-kompetisi', 'puspresma:bersihkan-tmp', 'puspresma:periksa-tautan', 'KirimPengingatTenggatKompetisi',
        'puspresma:ringkasan-antrean', 'puspresma:pengingat-pembimbing', 'activitylog:clean', 'queue:prune-failed --hours=720'] as $perintah) {
        expect($deskripsi)->toContain($perintah);
    }
    expect($acara->every(fn ($e) => $e->onOneServer && $e->withoutOverlapping))->toBeTrue();
});

it('mengirim pengingat H-3 sekali walau dijalankan dua kali, lalu H-1 sekali', function () {
    Notification::fake();
    $mhs = Mahasiswa::factory()->create();
    $user = User::factory()->create(['email' => $mhs->email])->assignRole('mahasiswa');
    $mhs->update(['user_id' => $user->id]);
    $kompetisi = Kompetisi::factory()->terbit()->create(['tenggat_pendaftaran' => now()->addDays(3)->setTime(6, 30)]);
    KompetisiMinat::create(['kompetisi_id' => $kompetisi->id, 'mahasiswa_id' => $mhs->id]);

    $this->travelTo(now()->setTime(7, 0));
    KirimPengingatTenggatKompetisi::dispatchSync();
    KirimPengingatTenggatKompetisi::dispatchSync();
    Notification::assertSentTo($user, PengingatTenggatKompetisi::class, fn ($n) => $n->hariLagi === 3);
    expect(KompetisiMinat::first()->diingatkan_h3_at)->not->toBeNull();

    $this->travel(2)->days();
    KirimPengingatTenggatKompetisi::dispatchSync();
    KirimPengingatTenggatKompetisi::dispatchSync();
    Notification::assertSentTo($user, PengingatTenggatKompetisi::class, fn ($n) => $n->hariLagi === 1);
    expect(KompetisiMinat::first()->diingatkan_h1_at)->not->toBeNull();
});

it('tidak mengingatkan kompetisi yang masih jauh, sudah lewat, atau mahasiswa tanpa akun', function () {
    Notification::fake();
    $mhs = Mahasiswa::factory()->create(['user_id' => null]);
    $jauh = Kompetisi::factory()->terbit()->create(['tenggat_pendaftaran' => now()->addDays(20)]);
    $dekat = Kompetisi::factory()->terbit()->create(['tenggat_pendaftaran' => now()->addDays(2)]);
    KompetisiMinat::create(['kompetisi_id' => $jauh->id, 'mahasiswa_id' => Mahasiswa::factory()->create()->id]);
    KompetisiMinat::create(['kompetisi_id' => $dekat->id, 'mahasiswa_id' => $mhs->id]);

    KirimPengingatTenggatKompetisi::dispatchSync();

    Notification::assertNothingSent();
});

it('tidak mengirim ringkasan bila antrean kosong dan mengirimnya ke admin-fakultas bila ada isi', function () {
    Notification::fake();
    $fakultas = User::factory()->create()->assignRole('admin-fakultas');

    $this->artisan('puspresma:ringkasan-antrean')->assertSuccessful();
    Notification::assertNothingSent();

    Prestasi::factory()->diajukan()->create(['diajukan_at' => now()->subDays(4)]);
    $this->artisan('puspresma:ringkasan-antrean')->assertSuccessful();
    Notification::assertSentTo($fakultas, RingkasanAntreanVerifikasi::class, fn ($n, $kanal) => $n->ringkasan['diajukan'] === 1 && $n->ringkasan['tertua_hari'] === 4 && $kanal === ['mail']);
});

it('mengirim ringkasan ke admin-prodi hanya untuk prodinya saat tahap prodi aktif', function () {
    Notification::fake();
    Pengaturan::setel('verifikasi_prodi_aktif', true);
    $p = Prestasi::factory()->diajukan()->create();
    $adminSesuai = User::factory()->create(['prodi_id' => $p->prodi_id])->assignRole('admin-prodi');
    $adminLain = User::factory()->create(['prodi_id' => Prodi::factory()->create()->id])->assignRole('admin-prodi');

    $this->artisan('puspresma:ringkasan-antrean')->assertSuccessful();

    Notification::assertSentTo($adminSesuai, RingkasanAntreanVerifikasi::class);
    Notification::assertNotSentTo($adminLain, RingkasanAntreanVerifikasi::class);
});

it('mengingatkan dosen untuk permintaan menunggu lebih dari 7 hari saja', function () {
    Notification::fake();
    $dosen = Dosen::factory()->create();
    $user = User::factory()->create()->assignRole('dosen');
    $dosen->update(['user_id' => $user->id]);
    Prestasi::factory()->diajukan()->create(['dosen_pembimbing_id' => $dosen->id, 'status_konfirmasi_pembimbing' => 'menunggu', 'diajukan_at' => now()->subDays(8)]);
    Prestasi::factory()->diajukan()->create(['dosen_pembimbing_id' => $dosen->id, 'status_konfirmasi_pembimbing' => 'menunggu', 'diajukan_at' => now()->subDays(2)]);

    $this->artisan('puspresma:pengingat-pembimbing')->assertSuccessful();

    Notification::assertSentTo($user, PengingatKonfirmasiPembimbing::class, fn ($n) => $n->jumlah === 1);
});

it('menghapus berkas sementara berumur lebih dari 24 jam dan mempertahankan yang lebih baru', function () {
    Storage::fake('tmp');
    $disk = Storage::disk('tmp');
    $disk->put('impor/lama.xlsx', 'x');
    $disk->put('galat/baru.xlsx', 'x');
    $disk->put('ekspor/lama.csv', 'x');
    touch($disk->path('impor/lama.xlsx'), now()->subHours(25)->getTimestamp());
    touch($disk->path('ekspor/lama.csv'), now()->subHours(48)->getTimestamp());
    touch($disk->path('galat/baru.xlsx'), now()->subHours(23)->getTimestamp());

    $this->artisan('puspresma:bersihkan-tmp')->assertSuccessful();

    $disk->assertMissing('impor/lama.xlsx');
    $disk->assertMissing('ekspor/lama.csv');
    $disk->assertExists('galat/baru.xlsx');
});

it('menandai riwayat impor yang berkas galatnya kedaluwarsa', function () {
    Storage::fake('tmp');
    $user = User::factory()->create();
    $riwayat = RiwayatImpor::create(['jenis' => 'mahasiswa', 'nama_berkas' => 'a.xlsx', 'path_galat' => 'galat/a.xlsx', 'kedaluwarsa_at' => now()->subHour(), 'user_id' => $user->id]);

    $this->artisan('puspresma:bersihkan-tmp')->assertSuccessful();

    expect($riwayat->refresh()->path_galat)->toBeNull();
});

it('mendispatch pemeriksaan untuk setiap tautan dengan jeda dan memprioritaskan terverifikasi dan foto', function () {
    Queue::fake();
    $biasa = Prestasi::factory()->create();
    $terverifikasi = Prestasi::factory()->terverifikasi()->create();
    $tautanBiasa = TautanBerkas::factory()->untukPrestasi($biasa)->sertifikat()->create();
    $tautanVerif = TautanBerkas::factory()->untukPrestasi($terverifikasi)->sertifikat()->create();
    $foto = TautanBerkas::factory()->untukPrestasi($biasa)->fotoPublik()->create();
    Queue::fake();

    $this->artisan('puspresma:periksa-tautan')->assertSuccessful();

    Queue::assertPushed(PeriksaTautanBerkas::class, 3);
    $urutan = collect(Queue::pushed(PeriksaTautanBerkas::class))->map(fn ($job) => $job->tautan->id)->all();
    expect(array_slice($urutan, 0, 2))->toContain($tautanVerif->id, $foto->id)->and($urutan[2])->toBe($tautanBiasa->id);
});

it('mengarsipkan kompetisi lewat perintah artisan', function () {
    $lewat = Kompetisi::factory()->terbit()->create(['tenggat_pendaftaran' => now()->subMinute()]);

    $this->artisan('puspresma:arsipkan-kompetisi')->assertSuccessful();

    expect($lewat->refresh()->status->value)->toBe('diarsipkan');
});

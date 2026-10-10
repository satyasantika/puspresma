<?php

use App\Actions\Katalog\TerbitkanKompetisi;
use App\Actions\Pembimbing\SangkalPembimbingan;
use App\Actions\Prestasi\AjukanPrestasi;
use App\Actions\Prestasi\KembalikanUntukPerbaikan;
use App\Actions\Prestasi\SimpanAnggotaTim;
use App\Actions\Prestasi\TolakPrestasi;
use App\Actions\Prestasi\VerifikasiPrestasi;
use App\Enums\StatusKonfirmasiPembimbing;
use App\Events\TautanBerkasTidakDapatDiakses as EventTautanMati;
use App\Jobs\DeteksiDuplikatPrestasi;
use App\Models\Dosen;
use App\Models\Kompetisi;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use App\Models\Prodi;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Notifications\AnggotaTimDitambahkan;
use App\Notifications\KandidatDuplikatDitemukan;
use App\Notifications\KompetisiBaruTerbit;
use App\Notifications\PembimbingMenyangkal;
use App\Notifications\PermintaanKonfirmasiPembimbing;
use App\Notifications\PrestasiDiajukan;
use App\Notifications\PrestasiDitolak;
use App\Notifications\PrestasiPerluPerbaikan;
use App\Notifications\PrestasiTerverifikasi;
use App\Notifications\TautanBerkasTidakDapatDiakses;
use App\Services\Pengaturan;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class]);
    Http::fake(['*' => Http::response('', 200)]);
    $this->prodi = Prodi::factory()->create();
    $this->fakultas = User::factory()->create()->assignRole('admin-fakultas');
    $this->fakultas2 = User::factory()->create()->assignRole('admin-fakultas');
    $this->adminProdi = User::factory()->create(['prodi_id' => $this->prodi->id])->assignRole('admin-prodi');
    $this->adminProdiLain = User::factory()->create(['prodi_id' => Prodi::factory()->create()->id])->assignRole('admin-prodi');

    $this->pengaju = Mahasiswa::factory()->for($this->prodi)->create();
    $this->userPengaju = User::factory()->create(['email' => $this->pengaju->email])->assignRole('mahasiswa');
    $this->pengaju->update(['user_id' => $this->userPengaju->id]);
    $this->anggota = Mahasiswa::factory()->create();
    $this->userAnggota = User::factory()->create(['email' => $this->anggota->email])->assignRole('mahasiswa');
    $this->anggota->update(['user_id' => $this->userAnggota->id]);
    $this->tanpaAkun = Mahasiswa::factory()->create(['user_id' => null]);

    $this->prestasi = Prestasi::factory()->kelompok()->diajukan()->for($this->pengaju, 'pengaju')->create();
    PrestasiAnggota::factory()->create(['prestasi_id' => $this->prestasi->id, 'mahasiswa_id' => $this->anggota->id]);
    PrestasiAnggota::factory()->create(['prestasi_id' => $this->prestasi->id, 'mahasiswa_id' => $this->tanpaAkun->id]);
    TautanBerkas::factory()->untukPrestasi($this->prestasi)->sertifikat()->create();
    Notification::fake();
});

it('memberi tahu semua admin-fakultas saat diajukan bila tahap prodi nonaktif, dan admin-prodi pengaju bila aktif', function () {
    $draf = Prestasi::factory()->kelompok()->for($this->pengaju, 'pengaju')->create();
    PrestasiAnggota::factory()->create(['prestasi_id' => $draf->id, 'mahasiswa_id' => $this->anggota->id]);
    TautanBerkas::factory()->untukPrestasi($draf)->sertifikat()->create();

    app(AjukanPrestasi::class)->jalankan($draf, $this->userPengaju);
    Notification::assertSentTo([$this->fakultas, $this->fakultas2], PrestasiDiajukan::class);
    Notification::assertNotSentTo([$this->adminProdi, $this->userPengaju], PrestasiDiajukan::class);

    Pengaturan::setel('verifikasi_prodi_aktif', true);
    $draf2 = Prestasi::factory()->kelompok()->for($this->pengaju, 'pengaju')->create();
    PrestasiAnggota::factory()->create(['prestasi_id' => $draf2->id, 'mahasiswa_id' => $this->anggota->id]);
    TautanBerkas::factory()->untukPrestasi($draf2)->sertifikat()->create();
    Notification::fake();

    app(AjukanPrestasi::class)->jalankan($draf2, $this->userPengaju);
    Notification::assertSentTo($this->adminProdi, PrestasiDiajukan::class);
    Notification::assertNotSentTo([$this->adminProdiLain, $this->fakultas], PrestasiDiajukan::class);
});

it('mengirim permintaan konfirmasi ke dosen pembimbing saat diajukan', function () {
    $dosen = Dosen::factory()->create();
    $userDosen = User::factory()->create()->assignRole('dosen');
    $dosen->update(['user_id' => $userDosen->id]);
    $draf = Prestasi::factory()->for($this->pengaju, 'pengaju')->create(['dosen_pembimbing_id' => $dosen->id]);
    TautanBerkas::factory()->untukPrestasi($draf)->sertifikat()->create();

    app(AjukanPrestasi::class)->jalankan($draf, $this->userPengaju);

    Notification::assertSentTo($userDosen, PermintaanKonfirmasiPembimbing::class);
});

it('mengirim perlu perbaikan ke anggota ber-akun saja dengan catatan di surel dan antre di notifikasi', function () {
    app(KembalikanUntukPerbaikan::class)->jalankan($this->prestasi, $this->fakultas, 'Mohon lengkapi sertifikat juara.');

    Notification::assertSentTo([$this->userPengaju, $this->userAnggota], PrestasiPerluPerbaikan::class, function ($n, $kanal) {
        $surat = $n->toMail($this->userPengaju);
        expect($n->queue)->toBe('notifikasi')->and($n->afterCommit)->toBeTrue()->and($kanal)->toBe(['database', 'mail'])
            ->and($surat->subject)->toBe('Prestasi Anda perlu perbaikan')
            ->and(implode(' ', $surat->introLines))->toContain('Mohon lengkapi sertifikat juara.');

        return true;
    });
    Notification::assertNotSentTo([$this->fakultas, $this->adminProdi], PrestasiPerluPerbaikan::class);
});

it('mencatat jumlah anggota tanpa akun yang dilewati', function () {
    Log::spy();

    app(KembalikanUntukPerbaikan::class)->jalankan($this->prestasi, $this->fakultas, 'Mohon lengkapi sertifikat juara.');

    Log::shouldHaveReceived('info')->withArgs(fn ($pesan, $konteks) => str_contains($pesan, 'tanpa akun') && $konteks['dilewati'] === 1);
});

it('mengirim terverifikasi ke anggota dan dosen pembimbing, dan ditolak hanya ke pengaju', function () {
    $dosen = Dosen::factory()->create();
    $userDosen = User::factory()->create()->assignRole('dosen');
    $dosen->update(['user_id' => $userDosen->id]);
    $this->prestasi->forceFill(['dosen_pembimbing_id' => $dosen->id, 'status_konfirmasi_pembimbing' => StatusKonfirmasiPembimbing::Dikonfirmasi])->save();

    app(VerifikasiPrestasi::class)->jalankan($this->prestasi, $this->fakultas);
    Notification::assertSentTo([$this->userPengaju, $this->userAnggota, $userDosen], PrestasiTerverifikasi::class);
    Notification::assertNotSentTo([$this->fakultas, $this->adminProdi], PrestasiTerverifikasi::class);

    $lain = Prestasi::factory()->diajukan()->for($this->pengaju, 'pengaju')->create();
    app(TolakPrestasi::class)->jalankan($lain, $this->fakultas, 'Bukan prestasi yang sah.');
    Notification::assertSentTo($this->userPengaju, PrestasiDitolak::class);
    Notification::assertNotSentTo($this->userAnggota, PrestasiDitolak::class);
});

it('memberi tahu pengaju saat tautan mati dan admin-fakultas hanya bila prestasi terverifikasi', function () {
    $tautan = $this->prestasi->tautanBukti()->first();

    EventTautanMati::dispatch($tautan);
    Notification::assertSentTo($this->userPengaju, TautanBerkasTidakDapatDiakses::class);
    Notification::assertNotSentTo($this->fakultas, TautanBerkasTidakDapatDiakses::class);

    $this->prestasi->forceFill(['status' => 'terverifikasi'])->save();
    EventTautanMati::dispatch($tautan->refresh());
    Notification::assertSentTo([$this->userPengaju, $this->fakultas, $this->fakultas2], TautanBerkasTidakDapatDiakses::class);
});

it('memberi tahu admin dan pengaju saat pembimbing menyangkal', function () {
    $dosen = Dosen::factory()->create();
    $userDosen = User::factory()->create()->assignRole('dosen');
    $dosen->update(['user_id' => $userDosen->id]);
    $this->prestasi->forceFill(['dosen_pembimbing_id' => $dosen->id, 'status_konfirmasi_pembimbing' => StatusKonfirmasiPembimbing::Menunggu])->save();

    app(SangkalPembimbingan::class)->jalankan($this->prestasi, $userDosen, 'Saya tidak membimbing kegiatan ini.');

    Notification::assertSentTo([$this->fakultas, $this->fakultas2, $this->userPengaju], PembimbingMenyangkal::class);
    Notification::assertNotSentTo($this->userAnggota, PembimbingMenyangkal::class);
});

it('memberi tahu admin-fakultas saat kandidat duplikat ditemukan', function () {
    $lama = Prestasi::factory()->diajukan()->create();
    TautanBerkas::factory()->untukPrestasi($lama)->create(['url' => 'https://drive.google.com/file/d/SAMA1/view']);
    TautanBerkas::factory()->untukPrestasi($this->prestasi)->create(['url' => 'https://drive.google.com/file/d/SAMA1/preview']);

    DeteksiDuplikatPrestasi::dispatchSync($this->prestasi);

    Notification::assertSentTo([$this->fakultas, $this->fakultas2], KandidatDuplikatDitemukan::class);
    Notification::assertNotSentTo($this->adminProdi, KandidatDuplikatDitemukan::class);
});

it('memberi tahu anggota baru yang punya akun saat ditambahkan ke tim', function () {
    $baru = Mahasiswa::factory()->create();
    $userBaru = User::factory()->create(['email' => $baru->email])->assignRole('mahasiswa');
    $baru->update(['user_id' => $userBaru->id]);
    $draf = Prestasi::factory()->kelompok()->for($this->pengaju, 'pengaju')->create();

    app(SimpanAnggotaTim::class)->jalankan($draf, [['mahasiswa_id' => $baru->id], ['mahasiswa_id' => $this->tanpaAkun->id]], [], [], $this->userPengaju);

    Notification::assertSentTo($userBaru, AnggotaTimDitambahkan::class);
    Notification::assertCount(1);
});

it('mengirim kompetisi baru hanya ke mahasiswa aktif prodi sasaran lewat database saja', function () {
    $sasaran = Mahasiswa::factory()->for($this->prodi)->create();
    $userSasaran = User::factory()->create(['email' => $sasaran->email])->assignRole('mahasiswa');
    $sasaran->update(['user_id' => $userSasaran->id]);
    $nonaktif = Mahasiswa::factory()->for($this->prodi)->create(['status_mahasiswa' => 'lulus']);
    $nonaktif->update(['user_id' => User::factory()->create(['email' => $nonaktif->email])->id]);
    $kompetisi = Kompetisi::factory()->create();
    $kompetisi->prodiSasaran()->sync([$this->prodi->id]);

    app(TerbitkanKompetisi::class)->jalankan($kompetisi, $this->fakultas);

    Notification::assertSentTo([$this->userPengaju, $userSasaran], KompetisiBaruTerbit::class, fn ($n, $kanal) => $kanal === ['database']);
    Notification::assertNotSentTo([$this->userAnggota, User::find($nonaktif->user_id)], KompetisiBaruTerbit::class);
});

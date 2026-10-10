<?php

namespace App\Actions\Prestasi;

use App\Enums\BentukPartisipasi;
use App\Enums\JenisTautan;
use App\Enums\StatusKonfirmasiPembimbing;
use App\Enums\StatusPrestasi;
use App\Exceptions\PrestasiTidakLengkap;
use App\Models\Prestasi;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/** Mengirim prestasi ke verifikasi (BR-04, BR-07, BR-11): periksa kelengkapan, beri nomor registrasi, ubah status. */
class AjukanPrestasi
{
    public function __construct(private readonly TransisiStatus $transisi) {}

    public function jalankan(Prestasi $prestasi, User $pelaku): Prestasi
    {
        Gate::forUser($pelaku)->authorize('ajukan', $prestasi);

        $kekurangan = $this->kekurangan($prestasi);
        if ($kekurangan !== []) {
            throw new PrestasiTidakLengkap($kekurangan);
        }

        $kunciLimit = 'ajukan-prestasi:'.$pelaku->getKey();
        if (RateLimiter::tooManyAttempts($kunciLimit, 10)) {
            throw ValidationException::withMessages(['status' => 'Terlalu banyak pengajuan; coba lagi nanti.']);
        }
        RateLimiter::hit($kunciLimit, 3600);

        $tambahan = ['diajukan_at' => now(), 'status_konfirmasi_pembimbing' => $this->konfirmasiPembimbing($prestasi)];

        if ($prestasi->nomor_registrasi !== null) {
            return $this->transisi->ubah($prestasi, StatusPrestasi::Diajukan, $pelaku, null, $tambahan);
        }

        $tahun = now()->year;

        // Lock tetap dipegang sampai transisi (dan nomor) ter-commit agar nomor tidak ganda.
        return Cache::lock("lock:nomor-registrasi:{$tahun}", 10)->block(5, fn (): Prestasi => $this->transisi->ubah(
            $prestasi, StatusPrestasi::Diajukan, $pelaku, null, $tambahan + ['nomor_registrasi' => $this->nomorBerikutnya($tahun)],
        ));
    }

    /**
     * Daftar kekurangan syarat ajukan (kosong = lengkap).
     *
     * @return list<string>
     */
    public function kekurangan(Prestasi $prestasi): array
    {
        $prestasi->loadMissing('tingkat', 'capaian');
        $k = [];

        foreach (['nama_kegiatan' => 'Nama kegiatan', 'penyelenggara' => 'Penyelenggara', 'jenis_kegiatan_id' => 'Jenis kegiatan',
            'bidang' => 'Bidang', 'tingkat_id' => 'Tingkat', 'capaian_id' => 'Capaian', 'moda' => 'Moda'] as $kolom => $nama) {
            if (blank($prestasi->{$kolom})) {
                $k[] = "{$nama} wajib diisi.";
            }
        }

        if ($prestasi->moda->perluLokasi() && blank($prestasi->lokasi)) {
            $k[] = 'Lokasi wajib diisi untuk kegiatan luring/hibrida.';
        }

        if ($prestasi->tanggal_mulai->gt($prestasi->tanggal_selesai) || $prestasi->tanggal_selesai->gt(now()->endOfDay())) {
            $k[] = 'Tanggal tidak valid: tanggal mulai ≤ tanggal selesai ≤ hari ini.';
        }

        if ($prestasi->capaian !== null && $prestasi->capaian->jenis_kegiatan_id !== $prestasi->jenis_kegiatan_id) {
            $k[] = 'Capaian tidak sesuai dengan jenis kegiatan.';
        }

        $kode = $prestasi->tingkat?->kode;
        if ($kode === 'nasional' && blank($prestasi->jumlah_pt_peserta)) {
            $k[] = 'Jumlah perguruan tinggi peserta wajib diisi untuk tingkat nasional.';
        }
        if ($kode === 'internasional' && (blank($prestasi->jumlah_negara_peserta) || blank($prestasi->negara_peserta))) {
            $k[] = 'Jumlah dan daftar negara peserta wajib diisi untuk tingkat internasional.';
        }

        $internal = $prestasi->anggota()->count();
        $eksternal = $prestasi->anggotaEksternal()->count();
        if ($prestasi->bentuk === BentukPartisipasi::Individu && ($internal !== 1 || $eksternal !== 0)) {
            $k[] = 'Prestasi individu hanya boleh memiliki pengaju sebagai anggota.';
        }
        if ($prestasi->bentuk === BentukPartisipasi::Kelompok && $internal + $eksternal < 2) {
            $k[] = 'Prestasi kelompok memerlukan minimal 2 anggota (FKIP dan luar FKIP).';
        }

        if (! $prestasi->tautanBukti()->where('jenis', JenisTautan::SertifikatPiagam->value)->exists()) {
            $k[] = 'Minimal satu tautan sertifikat/piagam wajib dilampirkan.';
        }

        return $k;
    }

    private function konfirmasiPembimbing(Prestasi $prestasi): StatusKonfirmasiPembimbing
    {
        if ($prestasi->dosen_pembimbing_id === null) {
            return StatusKonfirmasiPembimbing::TidakAda;
        }

        return in_array($prestasi->status_konfirmasi_pembimbing, [StatusKonfirmasiPembimbing::Dikonfirmasi, StatusKonfirmasiPembimbing::Disangkal], true)
            ? $prestasi->status_konfirmasi_pembimbing
            : StatusKonfirmasiPembimbing::Menunggu;
    }

    /** PRS-{tahun}-{urut 5 digit}; dipanggil di bawah lock tahunan. */
    private function nomorBerikutnya(int $tahun): string
    {
        return DB::transaction(function () use ($tahun): string {
            $maks = Prestasi::withTrashed()->where('nomor_registrasi', 'like', "PRS-{$tahun}-%")
                ->lockForUpdate()->max('nomor_registrasi');
            $urut = $maks === null ? 1 : ((int) substr((string) $maks, -5)) + 1;

            return sprintf('PRS-%d-%05d', $tahun, $urut);
        });
    }
}

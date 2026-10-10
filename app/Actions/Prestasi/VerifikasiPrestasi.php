<?php

namespace App\Actions\Prestasi;

use App\Actions\Bukti\SimpanSnapshotTautan;
use App\Enums\StatusKonfirmasiPembimbing;
use App\Enums\StatusPrestasi;
use App\Models\Prestasi;
use App\Models\User;
use App\Services\Pengaturan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Verifikasi final (BR-07/09/10/11/28/29): hanya admin-fakultas. Menyimpan snapshot tautan dalam transaksi yang sama
 * dan menaikkan versi cache statistik/galeri setelah commit.
 */
class VerifikasiPrestasi
{
    public function __construct(private readonly TransisiStatus $transisi, private readonly SimpanSnapshotTautan $snapshot) {}

    public function jalankan(
        Prestasi $prestasi,
        User $pelaku,
        ?string $catatan = null,
        bool $ambilAlih = false,
        bool $tanpaKonfirmasiPembimbing = false,
        bool $sudahMembukaTautan = true,
    ): Prestasi {
        Gate::forUser($pelaku)->authorize('verifikasi', $prestasi);

        if (! $sudahMembukaTautan) {
            throw ValidationException::withMessages(['sudah_membuka_tautan' => 'Anda wajib membuka dan memeriksa semua tautan bukti sebelum memverifikasi.']);
        }

        $this->periksaTahapProdi($prestasi, $catatan, $ambilAlih);

        if ($prestasi->adaKandidatDuplikatTerbuka()) {
            throw ValidationException::withMessages(['status' => 'Tinjau kandidat duplikat terlebih dahulu.']);
        }

        $this->periksaPembimbing($prestasi, $catatan, $tanpaKonfirmasiPembimbing);

        $hasil = DB::transaction(function () use ($prestasi, $pelaku, $catatan, $tanpaKonfirmasiPembimbing): Prestasi {
            $hasil = $this->transisi->ubah($prestasi, StatusPrestasi::Terverifikasi, $pelaku, $catatan, [
                'diverifikasi_at' => now(),
                'diverifikasi_oleh' => $pelaku->getKey(),
                'verifikasi_tanpa_konfirmasi' => $tanpaKonfirmasiPembimbing,
                'catatan_verifikasi' => filled($catatan) ? trim($catatan) : null,
            ]);

            $this->snapshot->jalankan($hasil, $pelaku);

            return $hasil;
        });

        DB::afterCommit(function (): void {
            Cache::increment('statistik:versi');
            Cache::increment('galeri:versi');
        });

        return $hasil;
    }

    /** Tahap prodi aktif: dari `diajukan` fakultas wajib "ambil alih" dengan catatan (BR-09). */
    private function periksaTahapProdi(Prestasi $prestasi, ?string $catatan, bool $ambilAlih): void
    {
        if ($prestasi->status !== StatusPrestasi::Diajukan || ! Pengaturan::ambil('verifikasi_prodi_aktif', false)) {
            return;
        }

        if (! $ambilAlih || mb_strlen(trim((string) $catatan)) < 10) {
            throw ValidationException::withMessages(['ambil_alih' => 'Tahap prodi aktif: centang "ambil alih" dan isi catatan (minimal 10 karakter) untuk memverifikasi langsung.']);
        }
    }

    private function periksaPembimbing(Prestasi $prestasi, ?string $catatan, bool $tanpaKonfirmasi): void
    {
        if ($prestasi->status_konfirmasi_pembimbing === StatusKonfirmasiPembimbing::Disangkal) {
            throw ValidationException::withMessages(['pembimbing' => 'Dosen pembimbing menyangkal. Kembalikan untuk perbaikan agar pembimbing diubah atau dihapus.']);
        }

        if ($prestasi->status_konfirmasi_pembimbing === StatusKonfirmasiPembimbing::Menunggu
            && (! $tanpaKonfirmasi || mb_strlen(trim((string) $catatan)) < 10)) {
            throw ValidationException::withMessages(['pembimbing' => 'Konfirmasi pembimbing belum ada: centang "verifikasi tanpa konfirmasi pembimbing" dan isi catatan (minimal 10 karakter).']);
        }
    }
}

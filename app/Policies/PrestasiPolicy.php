<?php

namespace App\Policies;

use App\Enums\StatusKonfirmasiPembimbing;
use App\Enums\StatusPrestasi;
use App\Models\Prestasi;
use App\Models\User;
use App\Services\Pengaturan;

/**
 * Aturan per record: milik/anggota, prodi, bimbingan (BR-03, BR-14, BR-15).
 */
class PrestasiPolicy
{
    public function viewAny(User $pelaku): bool
    {
        return $pelaku->canAny(['prestasi.buat-milik', 'prestasi.lihat-semua', 'prestasi.lihat-prodi', 'prestasi.lihat-bimbingan']);
    }

    /** Anggota, dosen pembimbing (bimbingan), pelihat semua, atau admin-prodi bila prestasi melibatkan prodinya. */
    public function view(User $pelaku, Prestasi $prestasi): bool
    {
        if ($pelaku->can('prestasi.lihat-semua')) {
            return true;
        }

        if ($this->anggota($pelaku, $prestasi)) {
            return true;
        }

        if ($pelaku->can('prestasi.lihat-bimbingan') && $this->pembimbing($pelaku, $prestasi)) {
            return true;
        }

        return $pelaku->can('prestasi.lihat-prodi')
            && $pelaku->prodi_id !== null
            && ($prestasi->relationLoaded('anggota')
                ? $prestasi->anggota->contains('prodi_id', $pelaku->prodi_id)
                : $prestasi->anggota()->where('prodi_id', $pelaku->prodi_id)->exists());
    }

    public function create(User $pelaku): bool
    {
        if ($pelaku->can('prestasi.buat-atas-nama')) {
            return true;
        }

        $pelaku->loadMissing('mahasiswa');

        return $pelaku->can('prestasi.buat-milik') && $pelaku->mahasiswa !== null;
    }

    /** Hanya pengaju atau admin-fakultas, hanya saat draf/perlu_perbaikan (BR-03). */
    public function update(User $pelaku, Prestasi $prestasi): bool
    {
        return $prestasi->status->bolehDiubahMahasiswa() && $this->pengajuAtauAdmin($pelaku, $prestasi);
    }

    /** Hapus hanya draf (BR-14). */
    public function delete(User $pelaku, Prestasi $prestasi): bool
    {
        return $prestasi->status === StatusPrestasi::Draf && $this->pengajuAtauAdmin($pelaku, $prestasi);
    }

    /**
     * Menambah/mengubah/menghapus tautan bukti (BR-06, BR-14): pengaju atau admin-fakultas saat draf/perlu_perbaikan;
     * admin-fakultas juga saat verifikasi berjalan. Terverifikasi/ditolak terkunci (BR-12/13).
     */
    public function kelolaBukti(User $pelaku, Prestasi $prestasi): bool
    {
        if ($prestasi->status->bolehDiubahMahasiswa()) {
            return $this->pengajuAtauAdmin($pelaku, $prestasi);
        }

        return in_array($prestasi->status, [StatusPrestasi::Diajukan, StatusPrestasi::LolosProdi], true)
            && $pelaku->can('prestasi.buat-atas-nama');
    }

    /** Hanya dosen yang ditunjuk, selama permintaan konfirmasi masih menunggu (BR-11). */
    public function jawabPembimbing(User $pelaku, Prestasi $prestasi): bool
    {
        return $pelaku->can('pembimbing.konfirmasi')
            && $this->pembimbing($pelaku, $prestasi)
            && $prestasi->status_konfirmasi_pembimbing === StatusKonfirmasiPembimbing::Menunggu;
    }

    /** Tahap 1: admin-prodi dari prodi pengaju, hanya bila tahap prodi aktif dan status diajukan (BR-09, BR-15). */
    public function loloskanProdi(User $pelaku, Prestasi $prestasi): bool
    {
        return $prestasi->status === StatusPrestasi::Diajukan && $this->adminProdiPengaju($pelaku, $prestasi);
    }

    /** Mengembalikan untuk perbaikan: verifikator fakultas, atau admin-prodi pengaju pada tahap 1. */
    public function kembalikan(User $pelaku, Prestasi $prestasi): bool
    {
        return $this->bisaMenilai($pelaku, $prestasi);
    }

    public function tolak(User $pelaku, Prestasi $prestasi): bool
    {
        return $this->bisaMenilai($pelaku, $prestasi);
    }

    /** Verifikasi final hanya oleh admin-fakultas (bukan super-admin, bukan admin-prodi). */
    public function verifikasi(User $pelaku, Prestasi $prestasi): bool
    {
        return $pelaku->can('prestasi.verifikasi')
            && in_array($prestasi->status, [StatusPrestasi::Diajukan, StatusPrestasi::LolosProdi], true);
    }

    /** Buka verifikasi (terverifikasi → perlu_perbaikan) oleh admin-fakultas (BR-12). */
    public function bukaVerifikasi(User $pelaku, Prestasi $prestasi): bool
    {
        return $pelaku->can('prestasi.buka-verifikasi') && $prestasi->status === StatusPrestasi::Terverifikasi;
    }

    /** Ganti tautan pada prestasi terverifikasi hanya oleh admin-fakultas (BR-30). */
    public function gantiTautan(User $pelaku, Prestasi $prestasi): bool
    {
        return $pelaku->can('prestasi.buka-verifikasi') && $prestasi->status === StatusPrestasi::Terverifikasi;
    }

    /** Penandaan "dilaporkan ke SIMKATMAWA" hanya untuk terverifikasi oleh admin-fakultas (BR-27). */
    public function tandaiSimkatmawa(User $pelaku, Prestasi $prestasi): bool
    {
        return $pelaku->can('simkatmawa.ekspor') && $prestasi->status === StatusPrestasi::Terverifikasi;
    }

    private function bisaMenilai(User $pelaku, Prestasi $prestasi): bool
    {
        if (! in_array($prestasi->status, [StatusPrestasi::Diajukan, StatusPrestasi::LolosProdi], true)) {
            return false;
        }

        return $pelaku->can('prestasi.verifikasi')
            || ($prestasi->status === StatusPrestasi::Diajukan && $this->adminProdiPengaju($pelaku, $prestasi));
    }

    private function adminProdiPengaju(User $pelaku, Prestasi $prestasi): bool
    {
        return (bool) Pengaturan::ambil('verifikasi_prodi_aktif', false)
            && $pelaku->can('prestasi.verifikasi-prodi')
            && $pelaku->prodi_id !== null
            && $pelaku->prodi_id === $prestasi->prodi_id;
    }

    /** Pengaju atau admin-fakultas dapat mengajukan dari draf/perlu_perbaikan. */
    public function ajukan(User $pelaku, Prestasi $prestasi): bool
    {
        return $prestasi->status->bolehDiubahMahasiswa() && $this->pengajuAtauAdmin($pelaku, $prestasi);
    }

    /** Hanya pengaju (bukan admin) yang menarik kembali, hanya dari diajukan. */
    public function tarikKembali(User $pelaku, Prestasi $prestasi): bool
    {
        return $prestasi->status === StatusPrestasi::Diajukan && $this->pengaju($pelaku, $prestasi);
    }

    public function pengajuAtauAdmin(User $pelaku, Prestasi $prestasi): bool
    {
        return $pelaku->can('prestasi.buat-atas-nama') || $this->pengaju($pelaku, $prestasi);
    }

    public function pengaju(User $pelaku, Prestasi $prestasi): bool
    {
        $pelaku->loadMissing('mahasiswa');

        return $pelaku->mahasiswa !== null && $pelaku->mahasiswa->id === $prestasi->pengaju_id;
    }

    public function anggota(User $pelaku, Prestasi $prestasi): bool
    {
        $pelaku->loadMissing('mahasiswa');

        if ($pelaku->mahasiswa === null) {
            return false;
        }

        // Pakai relasi yang sudah dimuat (tabel) agar tidak ada kueri per baris.
        return $prestasi->relationLoaded('anggota')
            ? $prestasi->anggota->contains('mahasiswa_id', $pelaku->mahasiswa->id)
            : $prestasi->anggota()->where('mahasiswa_id', $pelaku->mahasiswa->id)->exists();
    }

    public function pembimbing(User $pelaku, Prestasi $prestasi): bool
    {
        $pelaku->loadMissing('dosen');

        return $pelaku->dosen !== null && $prestasi->dosen_pembimbing_id === $pelaku->dosen->id;
    }
}

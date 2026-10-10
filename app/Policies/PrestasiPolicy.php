<?php

namespace App\Policies;

use App\Enums\StatusPrestasi;
use App\Models\Prestasi;
use App\Models\User;

/**
 * Aturan per record: milik/anggota, prodi, bimbingan (BR-03, BR-14, BR-15). Diperluas di F5.1.
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
            && $prestasi->anggota()->where('prodi_id', $pelaku->prodi_id)->exists();
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

        return $pelaku->mahasiswa !== null
            && $prestasi->anggota()->where('mahasiswa_id', $pelaku->mahasiswa->id)->exists();
    }

    public function pembimbing(User $pelaku, Prestasi $prestasi): bool
    {
        $pelaku->loadMissing('dosen');

        return $pelaku->dosen !== null && $prestasi->dosen_pembimbing_id === $pelaku->dosen->id;
    }
}

<?php

namespace App\Policies;

use App\Models\Mahasiswa;
use App\Models\User;

class MahasiswaPolicy
{
    public function viewAny(User $pelaku): bool
    {
        return $pelaku->can('master.kelola')
            || $pelaku->can('prestasi.lihat-prodi')
            || $pelaku->can('prestasi.lihat-semua');
    }

    /** Pengelola master dan pelihat semua: bebas; pelihat prodi: hanya prodinya (IDOR). */
    public function view(User $pelaku, Mahasiswa $mahasiswa): bool
    {
        if ($pelaku->can('master.kelola') || $pelaku->can('prestasi.lihat-semua')) {
            return true;
        }

        return $pelaku->can('prestasi.lihat-prodi')
            && $pelaku->prodi_id !== null
            && $pelaku->prodi_id === $mahasiswa->prodi_id;
    }

    public function create(User $pelaku): bool
    {
        return $pelaku->can('master.kelola');
    }

    public function update(User $pelaku, Mahasiswa $mahasiswa): bool
    {
        return $pelaku->can('master.kelola');
    }

    public function delete(User $pelaku, Mahasiswa $mahasiswa): bool
    {
        return $pelaku->can('master.kelola');
    }

    public function deleteAny(User $pelaku): bool
    {
        return $pelaku->can('master.kelola');
    }

    public function buatAkun(User $pelaku, Mahasiswa $mahasiswa): bool
    {
        return $pelaku->can('master.kelola');
    }
}

<?php

namespace App\Policies;

use App\Models\Dosen;
use App\Models\User;

class DosenPolicy
{
    public function viewAny(User $pelaku): bool
    {
        return $pelaku->can('master.kelola')
            || $pelaku->can('prestasi.lihat-prodi')
            || $pelaku->can('prestasi.lihat-semua');
    }

    /** Pengelola master dan pelihat semua: bebas; pelihat prodi: hanya prodinya (IDOR). */
    public function view(User $pelaku, Dosen $dosen): bool
    {
        if ($pelaku->can('master.kelola') || $pelaku->can('prestasi.lihat-semua')) {
            return true;
        }

        return $pelaku->can('prestasi.lihat-prodi')
            && $pelaku->prodi_id !== null
            && $pelaku->prodi_id === $dosen->prodi_id;
    }

    public function create(User $pelaku): bool
    {
        return $pelaku->can('master.kelola');
    }

    public function update(User $pelaku, Dosen $dosen): bool
    {
        return $pelaku->can('master.kelola');
    }

    public function delete(User $pelaku, Dosen $dosen): bool
    {
        return $pelaku->can('master.kelola');
    }

    public function deleteAny(User $pelaku): bool
    {
        return $pelaku->can('master.kelola');
    }

    public function buatAkun(User $pelaku, Dosen $dosen): bool
    {
        return $pelaku->can('master.kelola');
    }
}

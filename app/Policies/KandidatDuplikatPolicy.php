<?php

namespace App\Policies;

use App\Models\KandidatDuplikat;
use App\Models\User;

class KandidatDuplikatPolicy
{
    public function viewAny(User $pelaku): bool
    {
        return $pelaku->canAny(['duplikat.tinjau', 'prestasi.lihat-prodi']);
    }

    /** Admin-fakultas semua; admin-prodi bila prestasi melibatkan prodinya (BR-15). */
    public function view(User $pelaku, KandidatDuplikat $kandidat): bool
    {
        if ($pelaku->can('duplikat.tinjau')) {
            return true;
        }

        return $pelaku->can('prestasi.lihat-prodi')
            && $pelaku->prodi_id !== null
            && $kandidat->prestasi->anggota()->where('prodi_id', $pelaku->prodi_id)->exists();
    }

    /** Meninjau hanya oleh pemilik izin duplikat.tinjau (bukan super-admin: pemisahan tugas). */
    public function tinjauDuplikat(User $pelaku, KandidatDuplikat $kandidat): bool
    {
        return $pelaku->can('duplikat.tinjau');
    }
}

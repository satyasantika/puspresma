<?php

namespace App\Policies;

use App\Models\SuratKeteranganPrestasi;
use App\Models\User;

/** Admin-fakultas menerbitkan/mencabut; admin-prodi & pimpinan melihat sesuai cakupan; mahasiswa melihat/mencetak miliknya. */
class SuratKeteranganPrestasiPolicy
{
    public function viewAny(User $pelaku): bool
    {
        return $pelaku->canAny(['surat.terbitkan', 'prestasi.lihat-semua', 'prestasi.lihat-prodi', 'prestasi.buat-milik']);
    }

    public function view(User $pelaku, SuratKeteranganPrestasi $surat): bool
    {
        if ($pelaku->canAny(['surat.terbitkan', 'prestasi.lihat-semua'])) {
            return true;
        }

        $pelaku->loadMissing('mahasiswa');
        if ($pelaku->mahasiswa !== null && $pelaku->mahasiswa->id === $surat->mahasiswa_id) {
            return true;
        }

        return $pelaku->can('prestasi.lihat-prodi') && $pelaku->prodi_id !== null && $surat->mahasiswa->prodi_id === $pelaku->prodi_id;
    }

    public function create(User $pelaku): bool
    {
        return $pelaku->can('surat.terbitkan');
    }

    public function cetak(User $pelaku, SuratKeteranganPrestasi $surat): bool
    {
        return $this->view($pelaku, $surat);
    }

    public function cabut(User $pelaku, SuratKeteranganPrestasi $surat): bool
    {
        return $pelaku->can('surat.terbitkan');
    }

    public function update(User $pelaku, SuratKeteranganPrestasi $surat): bool
    {
        return false;
    }

    public function delete(User $pelaku, SuratKeteranganPrestasi $surat): bool
    {
        return false;
    }
}

<?php

namespace App\Policies;

use App\Models\Prestasi;
use App\Models\TautanBerkas;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Tautan bukti prestasi hanya untuk anggota, dosen pembimbing, admin-fakultas, dan admin-prodi yang prodinya terlibat
 * (matriks PRD §3.2: pimpinan tidak melihat tautan bukti). Pemilik lain mendelegasikan ke policy pemiliknya.
 */
class TautanBerkasPolicy
{
    public function view(User $pelaku, TautanBerkas $tautan): bool
    {
        $pemilik = $tautan->pemilik;

        if ($pemilik === null) {
            return false;
        }

        if (! $pemilik instanceof Prestasi) {
            return Gate::forUser($pelaku)->allows('view', $pemilik);
        }

        $kebijakan = Gate::forUser($pelaku);

        if ($pelaku->canAny(['prestasi.verifikasi', 'prestasi.buat-atas-nama'])) {
            return true;
        }

        if ($kebijakan->allows('anggota', $pemilik) || $kebijakan->allows('pembimbing', $pemilik) && $pelaku->can('prestasi.lihat-bimbingan')) {
            return true;
        }

        return $pelaku->can('prestasi.lihat-prodi')
            && $pelaku->prodi_id !== null
            && $pemilik->anggota()->where('prodi_id', $pelaku->prodi_id)->exists();
    }

    public function update(User $pelaku, TautanBerkas $tautan): bool
    {
        $pemilik = $tautan->pemilik;

        return $pemilik instanceof Prestasi && Gate::forUser($pelaku)->allows('kelolaBukti', $pemilik);
    }

    public function delete(User $pelaku, TautanBerkas $tautan): bool
    {
        return $this->update($pelaku, $tautan);
    }
}

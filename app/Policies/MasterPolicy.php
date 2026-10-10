<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Satu Policy untuk Prodi, JenisKegiatan, KategoriLomba, Tingkat, dan Capaian. */
class MasterPolicy
{
    /** Semua peran panel admin boleh melihat; hanya master.kelola yang boleh mengubah. */
    public function viewAny(User $pelaku): bool
    {
        return $pelaku->hasAnyRole(User::PERAN_PANEL['admin']);
    }

    public function view(User $pelaku, Model $record): bool
    {
        return $this->viewAny($pelaku);
    }

    public function create(User $pelaku): bool
    {
        return $pelaku->can('master.kelola');
    }

    public function update(User $pelaku, Model $record): bool
    {
        return $pelaku->can('master.kelola');
    }

    public function delete(User $pelaku, Model $record): bool
    {
        return $pelaku->can('master.kelola');
    }

    public function deleteAny(User $pelaku): bool
    {
        return $pelaku->can('master.kelola');
    }

    public function restore(User $pelaku, Model $record): bool
    {
        return $pelaku->can('master.kelola');
    }

    public function restoreAny(User $pelaku): bool
    {
        return $pelaku->can('master.kelola');
    }

    public function reorder(User $pelaku): bool
    {
        return $pelaku->can('master.kelola');
    }
}

<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Admin-fakultas hanya boleh melihat daftar pengguna (tanpa ubah). */
    public function viewAny(User $pelaku): bool
    {
        return $pelaku->can('pengguna.kelola') || $pelaku->hasRole('admin-fakultas');
    }

    public function view(User $pelaku, User $target): bool
    {
        return $this->viewAny($pelaku);
    }

    public function create(User $pelaku): bool
    {
        return $pelaku->can('pengguna.kelola');
    }

    public function update(User $pelaku, User $target): bool
    {
        return $pelaku->can('pengguna.kelola');
    }

    /** Tidak boleh menghapus diri sendiri. */
    public function delete(User $pelaku, User $target): bool
    {
        return $pelaku->can('pengguna.kelola') && ! $pelaku->is($target);
    }

    public function deleteAny(User $pelaku): bool
    {
        return $pelaku->can('pengguna.kelola');
    }

    public function resetMfa(User $pelaku, User $target): bool
    {
        return $pelaku->hasRole('super-admin');
    }
}

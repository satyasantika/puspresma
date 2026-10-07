<?php

namespace App\Policies;

use App\Models\Aktivitas;
use App\Models\User;

/** Log aktivitas hanya-baca; dapat dilihat pemegang permission audit.lihat. */
class AktivitasPolicy
{
    public function viewAny(User $pelaku): bool
    {
        return $pelaku->can('audit.lihat');
    }

    public function view(User $pelaku, Aktivitas $aktivitas): bool
    {
        return $pelaku->can('audit.lihat');
    }

    public function create(User $pelaku): bool
    {
        return false;
    }

    public function update(User $pelaku, Aktivitas $aktivitas): bool
    {
        return false;
    }

    public function delete(User $pelaku, Aktivitas $aktivitas): bool
    {
        return false;
    }

    public function deleteAny(User $pelaku): bool
    {
        return false;
    }
}

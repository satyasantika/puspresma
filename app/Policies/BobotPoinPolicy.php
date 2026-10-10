<?php

namespace App\Policies;

use App\Models\BobotPoin;
use App\Models\User;

class BobotPoinPolicy
{
    public function viewAny(User $pelaku): bool
    {
        return $pelaku->can('poin.kelola');
    }

    public function view(User $pelaku, BobotPoin $bobot): bool
    {
        return $pelaku->can('poin.kelola');
    }

    public function create(User $pelaku): bool
    {
        return $pelaku->can('poin.kelola');
    }

    public function update(User $pelaku, BobotPoin $bobot): bool
    {
        return $pelaku->can('poin.kelola');
    }

    public function delete(User $pelaku, BobotPoin $bobot): bool
    {
        return $pelaku->can('poin.kelola');
    }

    public function deleteAny(User $pelaku): bool
    {
        return $pelaku->can('poin.kelola');
    }
}

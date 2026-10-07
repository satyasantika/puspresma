<?php

namespace App\Actions\Pengguna;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class HapusPengguna
{
    public function jalankan(User $user, User $pelaku): void
    {
        if (! $pelaku->can('delete', $user)) {
            throw new AuthorizationException('Anda tidak berhak menghapus pengguna ini.');
        }

        if ($user->is($pelaku)) {
            throw ValidationException::withMessages(['pengguna' => 'Anda tidak dapat menghapus akun sendiri.']);
        }

        if ($user->hasRole('super-admin') && User::role('super-admin')->whereKeyNot($user->getKey())->doesntExist()) {
            throw ValidationException::withMessages(['pengguna' => 'Super-admin terakhir tidak dapat dihapus.']);
        }

        $user->delete();
    }
}

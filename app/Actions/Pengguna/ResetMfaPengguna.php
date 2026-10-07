<?php

namespace App\Actions\Pengguna;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

/** Menghapus secret MFA dan kode pemulihan; hanya super-admin. Rahasia lama tidak pernah dicatat. */
class ResetMfaPengguna
{
    public function jalankan(User $user, User $pelaku): void
    {
        if (! $pelaku->can('resetMfa', $user)) {
            throw new AuthorizationException('Hanya super-admin yang dapat mereset MFA.');
        }

        $user->forceFill([
            'app_authentication_secret' => null,
            'app_authentication_recovery_codes' => null,
        ])->saveQuietly();

        activity('pengguna')
            ->event('mfa_direset')
            ->causedBy($pelaku)
            ->performedOn($user)
            ->log('MFA pengguna direset');
    }
}

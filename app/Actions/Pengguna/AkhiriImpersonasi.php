<?php

namespace App\Actions\Pengguna;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/** Kembali ke akun super-admin asal. Bila akun asal tidak lagi sah (nonaktif/bukan super-admin), sesi diakhiri sepenuhnya. */
class AkhiriImpersonasi
{
    /** @return bool true bila kembali ke akun asal; false bila terpaksa keluar */
    public function jalankan(): bool
    {
        $asal = User::find(session('impersonator_id'));
        $target = Auth::user();

        if ($asal instanceof User && $target instanceof User) {
            activity('pengguna')->event('impersonasi_selesai')->causedBy($asal)->performedOn($target)
                ->log("Super-admin kembali dari akun {$target->email}");
        }

        request()->attributes->set('tanpa_catat_login', true);
        session()->forget('impersonator_id');

        if (! $asal instanceof User || ! $asal->is_active || ! $asal->hasRole('super-admin')) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            return false;
        }

        Auth::login($asal);
        session()->regenerate();

        return true;
    }
}

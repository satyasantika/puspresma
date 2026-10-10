<?php

namespace App\Actions\Pengguna;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

/**
 * Keadaan darurat: super-admin masuk sebagai pengguna lain tanpa kata sandinya. Setiap awal dan akhir dicatat di
 * activitylog, dan selama berlangsung semua aktivitas membawa `impersonator_id` (lihat model Aktivitas).
 * Tidak dapat bersarang, tidak berlaku untuk diri sendiri atau akun nonaktif.
 */
class MulaiImpersonasi
{
    /** Alasan penolakan, atau null bila diperbolehkan. */
    public static function alasanDitolak(User $pelaku, User $target): ?string
    {
        return match (true) {
            ! $pelaku->hasRole('super-admin') => 'Hanya super-admin yang dapat masuk sebagai pengguna lain.',
            session()->has('impersonator_id') => 'Akhiri sesi "masuk sebagai" yang sedang berjalan terlebih dahulu.',
            $pelaku->is($target) => 'Anda tidak dapat masuk sebagai diri sendiri.',
            ! $target->is_active => 'Akun nonaktif tidak dapat dimasuki. Aktifkan akun lebih dahulu.',
            default => null,
        };
    }

    public function jalankan(User $pelaku, User $target): void
    {
        if (($alasan = self::alasanDitolak($pelaku, $target)) !== null) {
            throw new AuthorizationException($alasan);
        }

        // Dicatat sebagai pelaku asli SEBELUM identitas berganti.
        activity('pengguna')->event('impersonasi_mulai')->causedBy($pelaku)->performedOn($target)
            ->withProperties(['ip' => request()->ip()])
            ->log("Super-admin masuk sebagai {$target->email}");

        // Peralihan identitas bukan "login" biasa: jangan mengubah last_login_at atau mencatat login palsu.
        request()->attributes->set('tanpa_catat_login', true);
        Auth::login($target);
        session()->regenerate();
        session()->put('impersonator_id', $pelaku->getKey());
    }
}

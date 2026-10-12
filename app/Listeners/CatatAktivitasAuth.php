<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;

/**
 * Mencatat login berhasil, login gagal, dan logout ke activitylog (log_name 'auth').
 * Hanya surel, IP, dan user agent yang disimpan; kredensial (kata sandi) tidak pernah dicatat.
 */
class CatatAktivitasAuth
{
    public function handle(Login|Failed|Logout $event): void
    {
        // Peralihan identitas "masuk sebagai" dicatat sendiri oleh MulaiImpersonasi/AkhiriImpersonasi.
        if (request()->attributes->get('tanpa_catat_login')) {
            return;
        }

        [$peristiwa, $deskripsi] = match (true) {
            $event instanceof Login => ['login', 'Login berhasil'],
            $event instanceof Failed => ['login_gagal', 'Login gagal'],
            default => ['logout', 'Logout'],
        };

        $pelaku = $event->user instanceof Model ? $event->user : null;
        $surel = $event instanceof Failed
            ? (string) ($event->credentials['email'] ?? '')
            : (string) $pelaku?->getAttribute('email');

        $aktivitas = activity('auth')
            ->event($peristiwa)
            ->withProperties([
                'email' => $surel,
                'ip' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
            ]);

        if ($pelaku !== null) {
            $aktivitas->causedBy($pelaku)->performedOn($pelaku);
        }

        $aktivitas->log($deskripsi);
    }
}

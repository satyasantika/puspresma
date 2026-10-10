<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;

/**
 * Akun yang ditandai super-admin (wajib_ganti_sandi) hanya dapat membuka halaman profil dan keluar
 * sampai kata sandinya diganti.
 */
class WajibGantiSandi
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User || ! $user->wajib_ganti_sandi || $this->diizinkan($request)) {
            return $next($request);
        }

        Notification::make()->title('Ganti kata sandi dahulu')
            ->body('Administrator mewajibkan Anda mengganti kata sandi sebelum melanjutkan.')
            ->warning()->persistent()->send();

        return redirect()->to(Filament::getProfileUrl() ?? Filament::getLogoutUrl());
    }

    private function diizinkan(Request $request): bool
    {
        if ($request->routeIs('filament.*.auth.profile', 'filament.*.auth.logout')) {
            return true;
        }

        // Aksi Livewire dari halaman profil itu sendiri (simpan formulir).
        $profil = Filament::getProfileUrl();

        return $profil !== null
            && $request->routeIs('livewire.*')
            && str_starts_with((string) $request->headers->get('referer'), $profil);
    }
}

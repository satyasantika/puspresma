<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengguna yang sudah masuk lalu membuka panel yang bukan miliknya dialihkan ke panel yang benar
 * (admin/pimpinan ke /admin; mahasiswa/dosen ke /portal), bukan menerima 403.
 */
class ArahkanKePanelSesuaiPeran
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $panel = Filament::getCurrentPanel();

        if (! $user instanceof User || $panel === null || ! $request->isMethodSafe() || $user->canAccessPanel($panel)) {
            return $next($request);
        }

        foreach (Filament::getPanels() as $lain) {
            if ($lain->getId() !== $panel->getId() && $user->canAccessPanel($lain)) {
                return redirect()->to(url($lain->getPath()));
            }
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan untuk semua respons (K14). CSP dibuat sempit tetapi kompatibel dengan Filament/Livewire/Alpine
 * (butuh 'unsafe-inline' dan 'unsafe-eval' untuk skrip; tanpa nonce) dan Vite (aset dibangun, tanpa host dev di produksi).
 * Gambar luar hanya lh3.googleusercontent.com (foto Drive); iframe hanya drive.google.com/docs.google.com (pratinjau).
 * CSP dilewati di lingkungan `local` karena server dev Vite memakai host lain; lihat docs/KEPUTUSAN.md K-15.
 */
class HeaderKeamanan
{
    public const CSP = "default-src 'self'; "
        ."script-src 'self' 'unsafe-inline' 'unsafe-eval'; "
        ."style-src 'self' 'unsafe-inline' https://fonts.bunny.net; "
        ."font-src 'self' data: https://fonts.bunny.net; "
        ."img-src 'self' data: https://lh3.googleusercontent.com; "
        .'frame-src https://drive.google.com https://docs.google.com; '
        ."connect-src 'self'; form-action 'self'; frame-ancestors 'self'; base-uri 'self'; object-src 'none'";

    public function handle(Request $request, Closure $next): Response
    {
        $respons = $next($request);

        $respons->headers->set('X-Content-Type-Options', 'nosniff');
        $respons->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $respons->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respons->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (! app()->environment('local')) {
            $respons->headers->set('Content-Security-Policy', self::CSP);
        }

        if (app()->isProduction()) {
            $respons->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $respons;
    }
}

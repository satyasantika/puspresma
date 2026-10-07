<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Auth\MultiFactor\MultiFactorChallenge;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

/**
 * Super-admin dan admin-fakultas (config puspresma.peran_wajib_mfa) wajib mengaktifkan MFA sebelum memakai panel.
 */
class WajibMfa
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User || ! $user->wajibMfa() || MultiFactorChallenge::make()->hasEnabledProviders($user)) {
            return $next($request);
        }

        return redirect()->guest(Filament::getSetUpRequiredMultiFactorAuthenticationUrl());
    }
}

<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;

/**
 * Halaman masuk bersama kedua panel: menolak akun nonaktif dengan pesan yang jelas.
 * Pesan khusus hanya muncul bila kata sandi benar, sehingga tidak membuka enumerasi akun.
 */
class Masuk extends Login
{
    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        $guard = Filament::auth();
        $provider = $guard->getProvider(); /** @phpstan-ignore-line */
        $credentials = $this->getCredentialsFromFormData($data);
        $user = $provider->retrieveByCredentials($credentials);

        if ($user instanceof Authenticatable
            && $provider->validateCredentials($user, $credentials)
            && ! $this->penggunaAktif($user)) {
            event(new Failed(Filament::getAuthGuard(), $user, $credentials));

            throw ValidationException::withMessages([
                'data.email' => 'Akun Anda dinonaktifkan',
            ]);
        }

        return parent::authenticate();
    }

    private function penggunaAktif(Authenticatable $user): bool
    {
        return (bool) ($user->is_active ?? true);
    }
}

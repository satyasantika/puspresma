<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\RateLimiter;
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
        $kunci = $this->batasiPercobaan((string) ($data['email'] ?? ''));
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

        $respons = parent::authenticate();

        if ($respons !== null) {
            RateLimiter::clear($kunci);
        }

        return $respons;
    }

    /** Menerapkan limiter 'login' (5/menit per surel+IP); mengembalikan kunci untuk dibersihkan saat sukses. */
    private function batasiPercobaan(string $surel): string
    {
        $permintaan = request()->duplicate(request: ['email' => $surel]);
        $batas = RateLimiter::limiter('login')($permintaan);
        $kunci = 'login:'.$batas->key;

        if (RateLimiter::tooManyAttempts($kunci, $batas->maxAttempts)) {
            throw ValidationException::withMessages([
                'data.email' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($kunci).' detik.',
            ]);
        }

        RateLimiter::hit($kunci, $batas->decaySeconds);

        return $kunci;
    }

    private function penggunaAktif(Authenticatable $user): bool
    {
        return (bool) ($user->is_active ?? true);
    }
}

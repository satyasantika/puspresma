<?php

namespace App\Http\Controllers;

use App\Actions\Auth\MasukDenganGoogle;
use App\Exceptions\MasukGoogleDitolak;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as PengalihanSymfony;

class GoogleAuthController extends Controller
{
    public function redirect(): PengalihanSymfony
    {
        $this->pastikanAktif();

        // Pengaman utama ada di callback (domain + master); parameter hd hanyalah petunjuk tampilan Google.
        /** @var AbstractProvider $penyedia */
        $penyedia = Socialite::driver('google');

        return $penyedia->scopes(['openid', 'email', 'profile'])->redirect();
    }

    public function callback(MasukDenganGoogle $masuk): RedirectResponse
    {
        $this->pastikanAktif();

        try {
            /** @var AbstractUser $penggunaGoogle */
            $penggunaGoogle = Socialite::driver('google')->user();
            $user = $masuk->jalankan($penggunaGoogle);
        } catch (MasukGoogleDitolak $e) {
            return $this->tolak($e->getMessage());
        } catch (InvalidStateException) {
            return $this->tolak('Sesi masuk Google kedaluwarsa. Silakan coba lagi.');
        }

        Auth::login($user);
        request()->session()->regenerate();

        $panel = $user->canAccessPanel(Filament::getPanel('portal')) ? 'portal' : 'admin';

        return redirect()->to(Filament::getPanel($panel)->getUrl());
    }

    private function pastikanAktif(): void
    {
        abort_unless(config('puspresma.google_login'), 404);
    }

    private function tolak(string $pesan): RedirectResponse
    {
        Notification::make()->title($pesan)->danger()->send();

        return redirect()->route('filament.admin.auth.login')->with('galat_google', $pesan);
    }
}

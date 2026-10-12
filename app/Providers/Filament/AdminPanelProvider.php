<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Pages\Auth\EditProfil;
use App\Filament\Pages\Auth\Masuk;
use App\Http\Middleware\ArahkanKePanelSesuaiPeran;
use App\Http\Middleware\WajibGantiSandi;
use App\Http\Middleware\WajibMfa;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $this->panelDasar($panel);

        if (config('puspresma.mfa_aktif')) {
            $panel->multiFactorAuthentication([AppAuthentication::make()->recoverable()], isRequired: true)
                ->multiFactorAuthenticationRequiredMiddlewareName(WajibMfa::class);
        }

        return $panel;
    }

    private function panelDasar(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Puspresma FKIP Unsil')
            ->login(Masuk::class)
            ->passwordReset()
            ->profile(EditProfil::class)
            // MFA TOTP + kode pemulihan: hanya bila MFA_AKTIF=true; wajib bagi peran di config puspresma.peran_wajib_mfa (WajibMfa).
            ->colors(['primary' => Color::Indigo])
            ->databaseNotifications()
            ->userMenuItems([
                Action::make('bantuan')->label('Bantuan')->icon('heroicon-o-question-mark-circle')
                    ->url(fn (): string => route('bantuan.index')),
                Action::make('panduan')->label('Panduan bergambar')->icon('heroicon-o-book-open')
                    ->url(fn (): string => asset('panduan/index.html'), shouldOpenInNewTab: true),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                ArahkanKePanelSesuaiPeran::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                WajibGantiSandi::class,
            ]);
    }
}

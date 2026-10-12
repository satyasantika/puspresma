<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfil;
use App\Filament\Pages\Auth\Masuk;
use App\Http\Middleware\ArahkanKePanelSesuaiPeran;
use App\Http\Middleware\WajibGantiSandi;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PortalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel

            ->id('portal')
            ->path('portal')
            ->brandName('Puspresma FKIP Unsil')
            ->login(Masuk::class)
            ->passwordReset()
            ->profile(EditProfil::class)
            ->colors(['primary' => Color::Indigo])
            ->databaseNotifications()
            ->userMenuItems([
                Action::make('bantuan')->label('Bantuan')->icon('heroicon-o-question-mark-circle')
                    ->url(fn (): string => route('bantuan.index')),
                Action::make('panduan')->label('Panduan bergambar')->icon('heroicon-o-book-open')
                    ->url(fn (): string => asset('panduan/index.html'), shouldOpenInNewTab: true),
            ])
            ->discoverResources(in: app_path('Filament/Portal/Resources'), for: 'App\\Filament\\Portal\\Resources')
            ->discoverPages(in: app_path('Filament/Portal/Pages'), for: 'App\\Filament\\Portal\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Portal/Widgets'), for: 'App\\Filament\\Portal\\Widgets')
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

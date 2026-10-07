<?php

namespace App\Providers;

use App\Listeners\CatatAktivitasAuth;
use App\Listeners\CatatLoginTerakhir;
use App\Listeners\CatatPerubahanPeran;
use Carbon\Carbon;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;

class AppServiceProvider extends ServiceProvider
{
    /** Ability Policy verifikasi prestasi yang selalu ditolak bagi super-admin (pemisahan tugas). */
    public const ABILITY_VERIFIKASI = ['verifikasi', 'loloskanProdi', 'bukaVerifikasi'];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('id');

        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Super-admin lolos semua pemeriksaan KECUALI verifikasi prestasi (pemisahan tugas).
        Gate::before(function ($user, string $ability) {
            if (! $user->hasRole('super-admin')) {
                return null;
            }

            return in_array($ability, self::ABILITY_VERIFIKASI, true) ? false : true;
        });

        Event::listen(Login::class, CatatLoginTerakhir::class);
        Event::listen([Login::class, Failed::class, Logout::class], CatatAktivitasAuth::class);
        Event::listen([RoleAttachedEvent::class, RoleDetachedEvent::class], CatatPerubahanPeran::class);

        // Login kedua panel: 5 percobaan per menit per kombinasi surel + IP.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));

        $this->aturUrlSubpath();
    }

    /**
     * Aplikasi berjalan di bawah awalan path (mis. /puspresma); semua URL mengikuti APP_URL.
     */
    protected function aturUrlSubpath(): void
    {
        $url = (string) config('app.url');
        $path = parse_url($url, PHP_URL_PATH);

        if ($path !== null && $path !== '' && $path !== '/') {
            URL::forceRootUrl($url);
        }

        if ($this->app->isProduction() || parse_url($url, PHP_URL_SCHEME) === 'https') {
            URL::forceScheme('https');
        }
    }
}

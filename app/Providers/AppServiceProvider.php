<?php

namespace App\Providers;

use App\Listeners\CatatLoginTerakhir;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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

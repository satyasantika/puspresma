<?php

namespace App\Providers;

use App\Contracts\PenyimpananBerkas;
use App\Events\AnggotaTimDitambahkan;
use App\Events\KandidatDuplikatDitemukan;
use App\Events\KompetisiDiterbitkan;
use App\Events\PembimbingMenjawab;
use App\Events\StatusPrestasiBerubah;
use App\Events\TautanBerkasTidakDapatDiakses;
use App\Jobs\KirimKompetisiBaruTerbit;
use App\Listeners\CatatAktivitasAuth;
use App\Listeners\CatatLoginTerakhir;
use App\Listeners\CatatPerubahanPeran;
use App\Listeners\DeteksiDuplikatSaatDiajukan;
use App\Listeners\HitungPoinSaatTerverifikasi;
use App\Listeners\KirimNotifikasiLainnya;
use App\Listeners\KirimNotifikasiStatusPrestasi;
use App\Models\Capaian;
use App\Models\JenisKegiatan;
use App\Models\KategoriLomba;
use App\Models\Kompetisi;
use App\Models\Prestasi;
use App\Models\Prodi;
use App\Models\Tingkat;
use App\Models\User;
use App\Policies\MasterPolicy;
use App\Services\Berkas\TautanEksternal;
use Carbon\Carbon;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;

class AppServiceProvider extends ServiceProvider
{
    /** Ability Policy verifikasi prestasi yang selalu ditolak bagi super-admin (pemisahan tugas). */
    public const ABILITY_VERIFIKASI = ['verifikasi', 'loloskanProdi', 'bukaVerifikasi', 'kembalikan', 'tolak', 'gantiTautan', 'tinjauDuplikat'];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PenyimpananBerkas::class, function (): PenyimpananBerkas {
            $mode = (string) config('berkas.mode');

            return match ($mode) {
                'tautan' => new TautanEksternal,
                default => throw new RuntimeException("Mode berkas '{$mode}' belum didukung."),
            };
        });
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
        Gate::before(function ($user, string $ability, array $arguments = []) {
            if (! $user->hasRole('super-admin')) {
                return null;
            }

            // Tidak ada yang boleh menghapus akunnya sendiri, termasuk super-admin.
            if ($ability === 'delete' && ($arguments[0] ?? null) instanceof User && $arguments[0]->is($user)) {
                return false;
            }

            return in_array($ability, self::ABILITY_VERIFIKASI, true) ? false : true;
        });

        foreach ([Prodi::class, JenisKegiatan::class, KategoriLomba::class, Tingkat::class, Capaian::class] as $model) {
            Gate::policy($model, MasterPolicy::class);
        }

        // Alias pemilik polimorfik tautan_berkas (docs/KEPUTUSAN.md K-11: morphMap, bukan enforceMorphMap, agar subjek activitylog tetap berjalan).
        Relation::morphMap([
            'prestasi' => Prestasi::class,
            'kompetisi' => Kompetisi::class,
        ]);

        Event::listen(StatusPrestasiBerubah::class, DeteksiDuplikatSaatDiajukan::class);
        Event::listen(StatusPrestasiBerubah::class, HitungPoinSaatTerverifikasi::class);
        Event::listen(StatusPrestasiBerubah::class, KirimNotifikasiStatusPrestasi::class);
        Event::listen(TautanBerkasTidakDapatDiakses::class, [KirimNotifikasiLainnya::class, 'tautanTidakDapatDiakses']);
        Event::listen(AnggotaTimDitambahkan::class, [KirimNotifikasiLainnya::class, 'anggotaDitambahkan']);
        Event::listen(KandidatDuplikatDitemukan::class, [KirimNotifikasiLainnya::class, 'kandidatDuplikat']);
        Event::listen(PembimbingMenjawab::class, [KirimNotifikasiLainnya::class, 'pembimbingMenjawab']);
        Event::listen(KompetisiDiterbitkan::class, fn (KompetisiDiterbitkan $e) => KirimKompetisiBaruTerbit::dispatch($e->kompetisi));
        Event::listen(Login::class, CatatLoginTerakhir::class);
        Event::listen([Login::class, Failed::class, Logout::class], CatatAktivitasAuth::class);
        Event::listen([RoleAttachedEvent::class, RoleDetachedEvent::class], CatatPerubahanPeran::class);

        // Login kedua panel: 5 percobaan per menit per kombinasi surel + IP.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));

        RateLimiter::for('galeri', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('katalog-publik', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('google-callback', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Tombol "Masuk dengan Google" di halaman masuk kedua panel, hanya bila fitur aktif.
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn () => config('puspresma.google_login') ? view('auth.tombol-google') : '',
        );

        RateLimiter::for('ajukan-prestasi', fn (Request $request) => Limit::perHour(10)->by($request->user()?->getAuthIdentifier() ?: $request->ip()));
        RateLimiter::for('simpan-tautan', fn (Request $request) => Limit::perHour(60)->by($request->user()?->getAuthIdentifier() ?: $request->ip()));
        RateLimiter::for('buka-tautan', fn (Request $request) => Limit::perHour(120)->by($request->user()?->getAuthIdentifier() ?: $request->ip()));

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

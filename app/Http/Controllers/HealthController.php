<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * GET /api/health — kesiapan integrasi FKIP Edu dan pemantauan (STANDAR-TEKNIS §1b, Profil Ringan).
 * Tidak pernah membocorkan pesan exception. 503 hanya bila basis data gagal; antrean dan detak penjadwal
 * bersifat informatif agar healthcheck kontainer tidak memicu restart berantai.
 */
class HealthController extends Controller
{
    private const DETAK_MAKS_MENIT = 5;

    public function __invoke(): JsonResponse
    {
        $database = $this->periksa(fn () => DB::select('select 1'));

        return response()->json([
            'status' => $database ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'version' => config('app.version'),
            'time' => now()->toIso8601String(),
            'checks' => [
                'database' => $database,
                'queue_size' => [
                    'notifikasi' => $this->ukuranAntrean((string) config('puspresma.antrean.notifikasi')),
                    'ekspor' => $this->ukuranAntrean((string) config('puspresma.antrean.ekspor')),
                ],
                'job_tertunda' => $this->hitung(fn () => DB::table('jobs')
                    ->whereNull('reserved_at')
                    ->where('available_at', '<=', now()->subMinutes(10)->getTimestamp())
                    ->count()),
                'job_gagal_24j' => $this->hitung(fn () => DB::table('failed_jobs')
                    ->where('failed_at', '>=', now()->subDay())
                    ->count()),
                'detak_penjadwal' => $this->detakPenjadwal(),
            ],
        ], $database ? 200 : 503);
    }

    /** ok | terlambat (lebih dari 5 menit) | belum-ada. */
    private function detakPenjadwal(): string
    {
        try {
            $detak = Cache::get(config('app.kode').':detak-penjadwal');
        } catch (Throwable) {
            return 'belum-ada';
        }

        if (! is_int($detak)) {
            return 'belum-ada';
        }

        return (now()->getTimestamp() - $detak) > self::DETAK_MAKS_MENIT * 60 ? 'terlambat' : 'ok';
    }

    /**
     * @param  callable(): mixed  $uji
     */
    private function periksa(callable $uji): bool
    {
        try {
            $uji();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  callable(): int  $hitung
     */
    private function hitung(callable $hitung): int
    {
        try {
            return (int) $hitung();
        } catch (Throwable) {
            return -1;
        }
    }

    private function ukuranAntrean(string $antrean): int
    {
        try {
            return Queue::size($antrean);
        } catch (Throwable) {
            return 0;
        }
    }
}

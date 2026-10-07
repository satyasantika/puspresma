<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * GET /api/health — kesiapan integrasi FKIP Edu dan pemantauan.
 * Tidak pernah membocorkan pesan exception.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = $this->periksa(fn () => DB::select('select 1'));
        $redis = $this->periksa(fn () => Redis::connection()->ping());

        $sehat = $database && $redis;

        return response()->json([
            'status' => $sehat ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'version' => config('app.version'),
            'time' => now()->toIso8601String(),
            'checks' => [
                'database' => $database,
                'redis' => $redis,
                'queue_size' => [
                    'notifikasi' => $redis ? $this->ukuranAntrean((string) config('puspresma.antrean.notifikasi')) : 0,
                    'ekspor' => $redis ? $this->ukuranAntrean((string) config('puspresma.antrean.ekspor')) : 0,
                ],
            ],
        ], $sehat ? 200 : 503);
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

    private function ukuranAntrean(string $antrean): int
    {
        try {
            return Queue::size($antrean);
        } catch (Throwable) {
            return 0;
        }
    }
}

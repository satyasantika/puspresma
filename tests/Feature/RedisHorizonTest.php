<?php

namespace Tests\Feature;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RedisHorizonTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private function envContoh(): array
    {
        $nilai = [];

        foreach (file(base_path('.env.example'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $baris) {
            if ($baris[0] !== '#' && str_contains($baris, '=')) {
                [$kunci, $isi] = explode('=', $baris, 2);
                $nilai[$kunci] = $isi;
            }
        }

        return $nilai;
    }

    public function test_env_contoh_memakai_redis_untuk_cache_sesi_dan_antrean(): void
    {
        $env = $this->envContoh();

        $this->assertSame('redis', $env['CACHE_STORE']);
        $this->assertSame('redis', $env['SESSION_DRIVER']);
        $this->assertSame('redis', $env['QUEUE_CONNECTION']);
        $this->assertSame('phpredis', $env['REDIS_CLIENT']);
        $this->assertSame('puspresma_', $env['REDIS_PREFIX']);
        $this->assertSame('40', $env['REDIS_DB']);
        $this->assertSame('41', $env['REDIS_CACHE_DB']);
        $this->assertSame('42', $env['REDIS_QUEUE_DB']);
    }

    public function test_blok_redis_terpisah_untuk_default_cache_dan_antrean(): void
    {
        $this->assertSame('cache', config('cache.stores.redis.connection'));
        $this->assertSame('queue', config('queue.connections.redis.connection'));
        $this->assertSame('41', (string) config('database.redis.cache.database'));
        $this->assertSame('42', (string) config('database.redis.queue.database'));
        $this->assertSame('40', (string) config('database.redis.default.database'));
    }

    public function test_horizon_memiliki_tiga_supervisor(): void
    {
        $defaults = config('horizon.defaults');

        $this->assertSame(['supervisor-notifikasi', 'supervisor-umum', 'supervisor-berat'], array_keys($defaults));
        $this->assertSame(['notifikasi'], $defaults['supervisor-notifikasi']['queue']);
        $this->assertSame(['default', 'berkas'], $defaults['supervisor-umum']['queue']);
        $this->assertSame(['impor', 'ekspor'], $defaults['supervisor-berat']['queue']);
        $this->assertSame(600, $defaults['supervisor-berat']['timeout']);
        $this->assertSame(512, $defaults['supervisor-berat']['memory']);

        foreach (['production', 'local'] as $lingkungan) {
            $this->assertSame(array_keys($defaults), array_keys(config("horizon.environments.$lingkungan")));
        }
    }

    public function test_job_dapat_didispatch_ke_antrean_ekspor(): void
    {
        Queue::fake();

        JobUjiEkspor::dispatch();

        Queue::assertPushedOn(config('puspresma.antrean.ekspor'), JobUjiEkspor::class);
    }
}

class JobUjiEkspor implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct()
    {
        $this->onQueue('ekspor');
    }

    public function handle(): void {}
}

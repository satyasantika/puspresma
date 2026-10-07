<?php

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Queue;

/**
 * @return array<string, string>
 */
function envContoh(): array
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

it('memakai redis untuk cache, sesi, dan antrean di env contoh', function () {
    $env = envContoh();

    expect($env['CACHE_STORE'])->toBe('redis')
        ->and($env['SESSION_DRIVER'])->toBe('redis')
        ->and($env['QUEUE_CONNECTION'])->toBe('redis')
        ->and($env['REDIS_CLIENT'])->toBe('phpredis')
        ->and($env['REDIS_PREFIX'])->toBe('puspresma_')
        ->and($env['REDIS_DB'])->toBe('40')
        ->and($env['REDIS_CACHE_DB'])->toBe('41')
        ->and($env['REDIS_QUEUE_DB'])->toBe('42');
});

it('memisahkan blok redis default, cache, dan antrean', function () {
    expect(config('cache.stores.redis.connection'))->toBe('cache')
        ->and(config('queue.connections.redis.connection'))->toBe('queue')
        ->and((string) config('database.redis.default.database'))->toBe('40')
        ->and((string) config('database.redis.cache.database'))->toBe('41')
        ->and((string) config('database.redis.queue.database'))->toBe('42');
});

it('menjalankan horizon dengan tiga supervisor', function () {
    $defaults = config('horizon.defaults');

    expect(array_keys($defaults))->toBe(['supervisor-notifikasi', 'supervisor-umum', 'supervisor-berat'])
        ->and($defaults['supervisor-notifikasi']['queue'])->toBe(['notifikasi'])
        ->and($defaults['supervisor-umum']['queue'])->toBe(['default', 'berkas'])
        ->and($defaults['supervisor-berat']['queue'])->toBe(['impor', 'ekspor'])
        ->and($defaults['supervisor-berat']['timeout'])->toBe(600)
        ->and($defaults['supervisor-berat']['memory'])->toBe(512);

    foreach (['production', 'local'] as $lingkungan) {
        expect(array_keys(config("horizon.environments.$lingkungan")))->toBe(array_keys($defaults));
    }
});

it('dapat mendispatch job ke antrean ekspor', function () {
    Queue::fake();

    JobUjiEkspor::dispatch();

    Queue::assertPushedOn(config('puspresma.antrean.ekspor'), JobUjiEkspor::class);
});

class JobUjiEkspor implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct()
    {
        $this->onQueue('ekspor');
    }

    public function handle(): void {}
}

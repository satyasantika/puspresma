<?php

use Illuminate\Support\Facades\Redis;

it('mengembalikan status ok dengan struktur json lengkap', function () {
    Redis::shouldReceive('connection->ping')->once()->andReturn(true);
    config(['app.version' => '9.9.9']);

    $this->getJson('/api/health')
        ->assertOk()
        ->assertJsonStructure([
            'status', 'app', 'version', 'time',
            'checks' => ['database', 'redis', 'queue_size' => ['notifikasi', 'ekspor']],
        ])
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('app', config('app.name'))
        ->assertJsonPath('version', '9.9.9')
        ->assertJsonPath('checks.database', true)
        ->assertJsonPath('checks.redis', true)
        ->assertJsonPath('checks.queue_size.notifikasi', 0)
        ->assertJsonPath('checks.queue_size.ekspor', 0);
});

it('mengembalikan 503 tanpa membocorkan galat bila redis gagal', function () {
    Redis::shouldReceive('connection->ping')->once()->andThrow(new RuntimeException('rahasia-host:6379 ditolak'));

    $this->getJson('/api/health')
        ->assertStatus(503)
        ->assertJsonPath('status', 'degraded')
        ->assertJsonPath('checks.redis', false)
        ->assertJsonPath('checks.database', true)
        ->assertDontSee('rahasia-host');
});

it('menyediakan route bernama health', function () {
    expect(route('health', absolute: false))->toBe('/api/health');
});

it('menampilkan versi aplikasi di footer halaman awal', function () {
    config(['app.version' => '1.2.3']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Puspresma FKIP Unsil · versi 1.2.3', false);
});

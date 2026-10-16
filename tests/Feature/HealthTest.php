<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.version' => '9.9.9']);
    cache()->put(config('app.kode').':detak-penjadwal', now(), 600);
});

it('mengembalikan status ok dengan struktur json lengkap tanpa Redis', function () {
    $this->getJson('/api/health')
        ->assertOk()
        ->assertJsonStructure([
            'status', 'app', 'version', 'time',
            'checks' => ['database', 'queue_size' => ['notifikasi', 'ekspor'], 'job_tertunda', 'job_gagal_24j', 'detak_penjadwal'],
        ])
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('app', config('app.name'))
        ->assertJsonPath('version', '9.9.9')
        ->assertJsonPath('checks.database', true)
        ->assertJsonPath('checks.queue_size.notifikasi', 0)
        ->assertJsonPath('checks.queue_size.ekspor', 0)
        ->assertJsonPath('checks.job_tertunda', 0)
        ->assertJsonPath('checks.job_gagal_24j', 0)
        ->assertJsonPath('checks.detak_penjadwal', 'ok')
        ->assertJsonMissingPath('checks.redis');
});

it('menghitung job tertunda lebih dari 10 menit tanpa mengubah status', function () {
    DB::table('jobs')->insert([
        ['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => now()->subMinutes(11)->getTimestamp(), 'created_at' => now()->subMinutes(11)->getTimestamp()],
        ['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => now()->subMinutes(2)->getTimestamp(), 'created_at' => now()->subMinutes(2)->getTimestamp()],
    ]);

    $this->getJson('/api/health')->assertOk()->assertJsonPath('status', 'ok')->assertJsonPath('checks.job_tertunda', 1);
});

it('menghitung job gagal dalam 24 jam terakhir', function () {
    $gagal = fn (string $kapan) => [
        'uuid' => (string) Str::uuid(), 'connection' => 'database', 'queue' => 'default',
        'payload' => '{}', 'exception' => 'x', 'failed_at' => $kapan,
    ];

    DB::table('failed_jobs')->insert([$gagal(now()->subHours(2)->toDateTimeString()), $gagal(now()->subDays(3)->toDateTimeString())]);

    $this->getJson('/api/health')->assertOk()->assertJsonPath('checks.job_gagal_24j', 1);
});

it('menandai detak penjadwal terlambat atau belum ada', function () {
    cache()->put(config('app.kode').':detak-penjadwal', now()->subMinutes(6), 600);
    $this->getJson('/api/health')->assertOk()->assertJsonPath('checks.detak_penjadwal', 'terlambat');

    cache()->forget(config('app.kode').':detak-penjadwal');
    $this->getJson('/api/health')->assertOk()->assertJsonPath('checks.detak_penjadwal', 'belum-ada');
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

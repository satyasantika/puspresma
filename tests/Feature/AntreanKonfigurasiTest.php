<?php

use App\Jobs\EksporBerkasJob;
use App\Jobs\ImporMahasiswaDariExcel;
use App\Models\BerkasEkspor;
use App\Models\RiwayatImpor;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Profil Ringan (STANDAR-TEKNIS §1b): dua antrean di database, worker dinyalakan penjadwal.
 */
it('mendefinisikan koneksi database dan database-berat dengan retry_after di atas timeout worker', function () {
    $ringan = config('queue.connections.database');
    $berat = config('queue.connections.database-berat');

    expect($ringan['driver'])->toBe('database')
        ->and($ringan['after_commit'])->toBeTrue()
        ->and($ringan['retry_after'])->toBeGreaterThan(60)   // --timeout worker default
        ->and($berat['driver'])->toBe('database')
        ->and($berat['queue'])->toBe('berat')
        ->and($berat['after_commit'])->toBeTrue()
        ->and($berat['retry_after'])->toBeGreaterThan(900);  // --timeout worker berat
});

it('memetakan nama antrean logis ke default dan berat saja', function () {
    expect(config('puspresma.antrean'))->toBe([
        'notifikasi' => 'default',
        'berkas' => 'default',
        'ekspor' => 'berat',
        'impor' => 'berat',
    ]);
});

it('menjadwalkan dua worker, detak penjadwal, dan pemangkasan antrean', function () {
    $jadwal = collect(app(Schedule::class)->events())
        ->map(fn ($e) => (string) ($e->command ?? $e->description))
        ->implode("\n");

    expect($jadwal)
        ->toContain('queue:work database --queue=default --stop-when-empty')
        ->toContain('queue:work database-berat --queue=berat --stop-when-empty')
        ->toContain('detak-penjadwal')
        ->toContain('queue:prune-failed')
        ->toContain('queue:prune-batches')
        ->not->toContain('horizon');
});

it('mengarahkan ekspor dan impor ke koneksi database-berat dan antrean berat', function () {
    $ekspor = new EksporBerkasJob(new BerkasEkspor);
    $impor = new ImporMahasiswaDariExcel(new RiwayatImpor);

    // phpunit menyetel PUSPRESMA_KONEKSI_BERAT=sync agar job berjalan inline; bawaan produksi dikunci di bawah.
    expect($ekspor->connection)->toBe(config('puspresma.koneksi_berat'))->and($ekspor->queue)->toBe('berat')
        ->and($impor->connection)->toBe(config('puspresma.koneksi_berat'))->and($impor->queue)->toBe('berat')
        ->and(file_get_contents(config_path('puspresma.php')))->toContain("env('PUSPRESMA_KONEKSI_BERAT', 'database-berat')");
});

it('mengantre notifikasi di antrean default', function () {
    expect(config('puspresma.antrean.notifikasi'))->toBe('default');
});

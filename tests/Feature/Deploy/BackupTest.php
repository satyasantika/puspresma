<?php

use App\Models\User;
use App\Support\PenerimaNotifikasiBackup;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Console\Scheduling\Schedule;

it('hanya mencadangkan database dan tidak memuat direktori storage', function () {
    expect(config('backup.backup.source.files.include'))->toBe([])
        ->and(config('backup.backup.source.databases'))->toHaveCount(1)
        ->and(config('backup.backup.source.files.exclude'))->toContain(storage_path('app/tmp'));
});

it('mengenkripsi arsip, menulis ke disk backup, dan memakai retensi 7/30/8/6', function () {
    expect(config('backup.backup.encryption'))->toBe('default')
        ->and(config('backup.backup.destination.disks'))->toHaveCount(1)
        ->and(config('filesystems.disks.backup.driver'))->toBe('local')
        ->and(config('backup.cleanup.default_strategy.keep_all_backups_for_days'))->toBe(7)
        ->and(config('backup.cleanup.default_strategy.keep_daily_backups_for_days'))->toBe(30)
        ->and(config('backup.cleanup.default_strategy.keep_weekly_backups_for_weeks'))->toBe(8)
        ->and(config('backup.cleanup.default_strategy.keep_monthly_backups_for_months'))->toBe(6);
});

it('mengirim notifikasi backup ke surel super-admin', function () {
    $this->seed(PeranDanIzinSeeder::class);
    User::factory()->create(['email' => 'root@unsil.ac.id'])->assignRole('super-admin');
    User::factory()->create(['email' => 'biasa@unsil.ac.id'])->assignRole('admin-fakultas');

    expect((new PenerimaNotifikasiBackup)->routeNotificationForMail())->toBe(['root@unsil.ac.id']);
});

it('mendaftarkan jadwal backup harian', function () {
    $jadwal = collect(app(Schedule::class)->events())
        ->mapWithKeys(fn ($e) => [$e->command => $e->expression]);

    $cocok = fn (string $perintah) => $jadwal->first(fn ($exp, $cmd) => str_contains($cmd, $perintah));

    expect($cocok('backup:run --only-db'))->toBe('0 1 * * *')
        ->and($cocok('backup:clean'))->toBe('30 1 * * *')
        ->and($cocok('backup:monitor'))->toBe('0 9 * * *');
});

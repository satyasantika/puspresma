<?php

use App\Models\User;
use App\Support\PenerimaNotifikasiBackup;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;

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

it('memetakan semua kelas notifikasi spatie/laravel-backup agar via() tidak pernah menemui kunci kosong', function () {
    // Spatie\Backup\Notifications\BaseNotification::via() mengakses
    // config('backup.notifications.notifications')[static::class] tanpa isset(): kunci yang
    // hilang membuat backup SUKSES pun gagal (Undefined array key) begitu dijalankan sungguhan
    // di produksi (bukan hanya di tes config seperti di atas).
    $wajib = [
        BackupHasFailedNotification::class,
        UnhealthyBackupWasFoundNotification::class,
        CleanupHasFailedNotification::class,
        BackupWasSuccessfulNotification::class,
        HealthyBackupWasFoundNotification::class,
        CleanupWasSuccessfulNotification::class,
    ];

    expect(array_keys(config('backup.notifications.notifications')))->toEqualCanonicalizing($wajib);
});

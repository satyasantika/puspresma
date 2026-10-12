<?php

use App\Support\PenerimaNotifikasiBackup;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

/*
| Backup Puspresma: HANYA basis data. Bukti prestasi berupa tautan (Google Drive), tidak ada berkas pengguna
| di server, sehingga tidak ada direktori yang dicadangkan. Arsip dienkripsi dengan BACKUP_ARCHIVE_PASSWORD.
*/
return [
    'backup' => [
        'name' => env('APP_NAME', 'puspresma'),

        'source' => [
            'files' => [
                'include' => [],
                'exclude' => [storage_path('app/tmp')],
                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => null,
            ],
            'databases' => [env('DB_CONNECTION', 'mysql')],
        ],

        'database_dump_compressor' => null,
        'database_dump_file_timestamp_format' => null,
        'database_dump_filename_base' => 'database',
        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level' => 9,
            'filename_prefix' => '',
            // Disk 'backup' = volume terpisah; ganti ke 's3' (S3/MinIO) lewat BACKUP_DISK.
            'disks' => [env('BACKUP_DISK', 'backup')],
            'continue_on_failure' => false,
        ],

        'temporary_directory' => storage_path('app/backup-temp'),
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'default',
        'verify_backup' => false,
        'tries' => 1,
        'retry_delay' => 0,
    ],

    'notifications' => [
        'notifications' => [
            BackupHasFailedNotification::class => ['mail'],
            UnhealthyBackupWasFoundNotification::class => ['mail'],
            CleanupHasFailedNotification::class => ['mail'],
        ],
        'notifiable' => PenerimaNotifikasiBackup::class,
        'mail' => [
            'to' => 'tidak-dipakai@example.invalid',
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'puspresma@unsil.ac.id'),
                'name' => env('MAIL_FROM_NAME', 'Puspresma'),
            ],
        ],
        'slack' => ['webhook_url' => '', 'channel' => null, 'username' => null, 'icon' => null],
        'discord' => ['webhook_url' => '', 'username' => '', 'avatar_url' => ''],
        'webhook' => ['url' => ''],
    ],

    'log_channel' => null,

    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'puspresma'),
            'disks' => [env('BACKUP_DISK', 'backup')],
            'health_checks' => [
                MaximumAgeInDays::class => 1,
                MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => DefaultStrategy::class,
        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 30,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 6,
            'keep_yearly_backups_for_years' => 0,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],
        'tries' => 1,
        'retry_delay' => 0,
    ],
];

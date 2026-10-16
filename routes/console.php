<?php

use App\Jobs\KirimPengingatTenggatKompetisi;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Penjadwal
|--------------------------------------------------------------------------
|
| Dijalankan oleh service `puspresma-scheduler` (php artisan schedule:work).
| Semua tugas memakai lock cache `database` (tabel cache_locks) agar tidak tumpang tindih / ganda.
| Penjadwal juga menyalakan worker antrean tiap menit (Profil Ringan, STANDAR-TEKNIS §1b).
|
*/

// Worker antrean: berhenti saat antrean kosong; jeda proses job paling lama ±1 menit.
// --memory=256 pada worker default melindungi RAM server (3,8 GiB).
Schedule::command('queue:work database --queue=default --stop-when-empty --max-time=55 --timeout=60 --tries=3 --memory=256')
    ->everyMinute()->onOneServer()->withoutOverlapping(2)->runInBackground();
Schedule::command('queue:work database-berat --queue=berat --stop-when-empty --max-time=600 --timeout=900 --tries=2 --memory=512')
    ->everyMinute()->onOneServer()->withoutOverlapping(30)->runInBackground();
// Detak penjadwal: dibaca /api/health; peringatan bila lebih tua dari 5 menit.
Schedule::call(fn () => cache()->put(config('app.kode').':detak-penjadwal', now(), 600))
    ->name('detak-penjadwal')->everyMinute()->onOneServer()->withoutOverlapping();
Schedule::command('queue:prune-batches --hours=168')->dailyAt('03:05')->onOneServer()->withoutOverlapping();
Schedule::command('puspresma:arsipkan-kompetisi')->dailyAt('00:30')->onOneServer()->withoutOverlapping();
Schedule::command('puspresma:bersihkan-tmp')->hourly()->onOneServer()->withoutOverlapping();
Schedule::command('puspresma:periksa-tautan')->weeklyOn(0, '02:00')->onOneServer()->withoutOverlapping();
Schedule::job(new KirimPengingatTenggatKompetisi)->dailyAt('07:00')->onOneServer()->withoutOverlapping();
Schedule::command('puspresma:ringkasan-antrean')->weekdays()->at('07:30')->onOneServer()->withoutOverlapping();
Schedule::command('puspresma:pengingat-pembimbing')->weeklyOn(1, '08:00')->onOneServer()->withoutOverlapping();
Schedule::command('activitylog:clean')->monthlyOn(1, '03:00')->onOneServer()->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=720')->monthlyOn(1, '03:30')->onOneServer()->withoutOverlapping();

// Backup basis data: bukti berupa tautan Drive, jadi hanya DB yang dicadangkan.
Schedule::command('backup:run --only-db')->dailyAt('01:00')->onOneServer()->withoutOverlapping();
Schedule::command('backup:clean')->dailyAt('01:30')->onOneServer()->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('09:00')->onOneServer()->withoutOverlapping();

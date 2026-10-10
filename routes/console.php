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
| Semua tugas memakai lock Redis agar tidak tumpang tindih / ganda.
|
*/

Schedule::command('horizon:snapshot')->everyFiveMinutes()->onOneServer()->withoutOverlapping();
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

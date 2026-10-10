<?php

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

arch('app/Enums hanya berisi enum')
    ->expect('App\Enums')
    ->toBeEnums();

arch('Actions tidak memakai facade Request')
    ->expect('App\Actions')
    ->not->toUse(['Illuminate\Support\Facades\Request', 'Request']);

arch('tidak ada dd, dump, atau ray di app')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'dump_server'])
    ->not->toBeUsed();

arch('semua Notification mengantre (ShouldQueue), tidak ada pengiriman sinkron')
    ->expect('App\Notifications')
    ->classes()
    ->toImplement(ShouldQueue::class)
    ->ignoring('App\Notifications\Channels');

arch('Notification aplikasi memakai kelas dasar yang mengantre di antrean notifikasi')
    ->expect('App\Notifications')
    ->classes()
    ->toExtend(Notification::class)
    ->ignoring('App\Notifications\Channels');

it('memakai antrean notifikasi dan afterCommit pada setiap Notification', function () {
    $kelas = collect(glob(app_path('Notifications/*.php')))
        ->map(fn ($f) => 'App\\Notifications\\'.basename($f, '.php'))
        ->reject(fn ($k) => (new ReflectionClass($k))->isAbstract());

    expect($kelas)->not->toBeEmpty();

    foreach ($kelas as $k) {
        $source = file_get_contents((new ReflectionClass($k))->getFileName());
        $dasar = str_contains($source, 'extends NotifikasiAplikasi') || str_contains($source, "onQueue(config('puspresma.antrean.notifikasi'))");
        expect($dasar)->toBeTrue("{$k} wajib mengantre di 'notifikasi'");
    }
});

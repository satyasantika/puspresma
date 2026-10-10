<?php

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

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

it('tidak menulis berkas ke storage kecuali berkas sementara (ekspor/galat) di disk tmp', function () {
    // Penulisan yang diizinkan: ekspor besar (EksporBerkasJob) dan tabel/galat impor di disk 'tmp' (≤ 24 jam).
    $diizinkan = ['Jobs/EksporBerkasJob.php', 'Jobs/ImporMasterDariExcel.php', 'Services/Ekspor/PenulisBerkas.php', 'Filament/Admin/Concerns/HalamanImpor.php'];
    $pelanggar = [];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path(), FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $berkas) {
        $relatif = str_replace(app_path().'/', '', $berkas->getPathname());
        if (! str_ends_with($relatif, '.php') || in_array($relatif, $diizinkan, true)) {
            continue;
        }
        if (preg_match('/Storage::(?:disk\([^)]*\)->)?(?:put|putFile|putFileAs|writeStream)\(|->storeAs?\(|file_put_contents\(|->move\(/', file_get_contents($berkas->getPathname()))) {
            $pelanggar[] = $relatif;
        }
    }

    expect($pelanggar)->toBe([]);
});

it('memakai UUID (bukan id numerik) pada route model sensitif', function () {
    $sensitif = ['prestasi', 'tautanBerkas', 'berkasEkspor', 'surat'];

    foreach (Route::getRoutes() as $rute) {
        foreach ($sensitif as $parameter) {
            if (in_array($parameter, $rute->parameterNames(), true)) {
                // Tanpa batasan numerik (whereNumber/regex \d+) pada parameter model sensitif.
                expect((string) ($rute->wheres[$parameter] ?? ''))->not->toMatch('/\\\\d|\[0-9\]/', "{$rute->uri()} membatasi {$parameter} ke angka");
            }
        }
    }

    $migrasi = collect(glob(database_path('migrations/*.php')))->filter(fn ($f) => str_contains($f, 'berkas_ekspor') || str_contains($f, 'surat_keterangan') || str_contains($f, 'tautan_berkas'));
    foreach ($migrasi as $f) {
        expect(file_get_contents($f))->toContain("uuid('id')->primary()");
    }
});

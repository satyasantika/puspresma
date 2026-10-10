<?php

use App\Actions\Notifikasi\KirimPesanUjiWhatsApp;
use App\Actions\Notifikasi\SimpanPreferensiWhatsApp;
use App\Filament\Admin\Pages\ProfilNotifikasi as ProfilAdmin;
use App\Filament\Portal\Pages\ProfilNotifikasi as ProfilPortal;
use App\Jobs\KirimPesanWhatsApp;
use App\Models\Aktivitas;
use App\Models\Prestasi;
use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\PrestasiPerluPerbaikan;
use App\Services\FonnteClient;
use App\Services\Pengaturan;
use App\Support\NomorWhatsApp;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class]);
    config(['services.fonnte.token' => 'RAHASIA-TOKEN-FONNTE', 'services.fonnte.url' => 'https://api.fonnte.test/send']);
    $this->user = User::factory()->create(['nomor_wa' => '6281234567890', 'notif_wa_aktif' => true])->assignRole('mahasiswa');
});

it('menormalkan nomor 08xx menjadi 62xx dan menolak nomor tidak valid', function () {
    expect(NomorWhatsApp::normalisasi('0812-3456-7890'))->toBe('6281234567890')
        ->and(NomorWhatsApp::normalisasi('+62 812 3456 7890'))->toBe('6281234567890')
        ->and(NomorWhatsApp::normalisasi('81234567890'))->toBe('6281234567890')
        ->and(NomorWhatsApp::normalisasi('12345'))->toBeNull()
        ->and(NomorWhatsApp::normalisasi(''))->toBeNull()
        ->and(NomorWhatsApp::samarkan('6281234567890'))->toBe('…7890');
});

it('mengirim pesan ke nomor yang benar dengan header token dan tanpa mencatat token di log', function () {
    Http::fake(['api.fonnte.test/*' => Http::response(['status' => true], 200)]);
    $logSebelum = file_exists(storage_path('logs/laravel.log')) ? filesize(storage_path('logs/laravel.log')) : 0;

    KirimPesanWhatsApp::dispatchSync('6281234567890', 'Halo uji');

    Http::assertSent(fn ($r) => $r->url() === 'https://api.fonnte.test/send' && $r['target'] === '6281234567890' && $r['message'] === 'Halo uji'
        && $r->header('Authorization')[0] === 'RAHASIA-TOKEN-FONNTE');
    $log = file_exists(storage_path('logs/laravel.log')) ? substr((string) file_get_contents(storage_path('logs/laravel.log')), $logSebelum) : '';
    expect($log)->not->toContain('RAHASIA-TOKEN-FONNTE')->not->toContain('6281234567890')
        ->and(Aktivitas::where('properties', 'like', '%RAHASIA-TOKEN%')->count())->toBe(0);
});

it('melempar exception saat gateway membalas 500 atau status false agar job diulang', function () {
    Http::fake(['api.fonnte.test/*' => Http::response('galat', 500)]);
    expect(fn () => KirimPesanWhatsApp::dispatchSync('6281234567890', 'x'))->toThrow(RuntimeException::class);

    Http::fake(['api.fonnte.test/*' => Http::response(['status' => false, 'reason' => 'invalid'], 200)]);
    expect(fn () => (new KirimPesanWhatsApp('6281234567890', 'x'))->handle(app(FonnteClient::class)))->toThrow(RuntimeException::class);

    $job = new KirimPesanWhatsApp('6281234567890', 'x');
    expect($job->tries)->toBe(3)->and($job->backoff)->toBe(60);
});

it('memakai kanal whatsapp hanya bila sistem, pengguna, dan nomor memenuhi syarat', function () {
    $prestasi = Prestasi::factory()->create();
    $notif = new PrestasiPerluPerbaikan($prestasi, 'Perbaiki tautan.');

    expect($notif->via($this->user))->toBe(['database', 'mail']);

    Pengaturan::setel('wa_aktif', true);
    expect($notif->via($this->user))->toBe(['database', 'mail', WhatsAppChannel::class]);

    $this->user->forceFill(['notif_wa_aktif' => false])->save();
    expect($notif->via($this->user->refresh()))->toBe(['database', 'mail']);

    $this->user->forceFill(['notif_wa_aktif' => true, 'nomor_wa' => 'bukan-nomor'])->save();
    expect($notif->via($this->user->refresh()))->toBe(['database', 'mail']);
});

it('menjadwalkan job WA terpisah dari kanal lain dengan pesan maksimal 1000 karakter', function () {
    Queue::fake();
    Pengaturan::setel('wa_aktif', true);
    $notif = new PrestasiPerluPerbaikan(Prestasi::factory()->create(), str_repeat('x', 3000));

    (new WhatsAppChannel)->send($this->user, $notif);

    Queue::assertPushed(KirimPesanWhatsApp::class, fn ($job) => $job->nomor === '6281234567890' && mb_strlen($job->pesan) <= 1000 && $job->queue === 'notifikasi');
});

it('menyimpan preferensi dengan normalisasi, menolak nomor tidak valid, dan mewajibkan nomor untuk aktivasi', function () {
    app(SimpanPreferensiWhatsApp::class)->jalankan($this->user, '0812 3456 7891', true);
    expect($this->user->refresh()->nomor_wa)->toBe('6281234567891')->and($this->user->notif_wa_aktif)->toBeTrue();

    expect(fn () => app(SimpanPreferensiWhatsApp::class)->jalankan($this->user, '12', false))->toThrow(ValidationException::class);
    expect(fn () => app(SimpanPreferensiWhatsApp::class)->jalankan($this->user, '', true))->toThrow(ValidationException::class);
});

it('membatasi pesan uji 3 kali per jam dan mensyaratkan wa_aktif', function () {
    Queue::fake();
    RateLimiter::clear('pesan-uji-wa:'.$this->user->id);
    expect(fn () => app(KirimPesanUjiWhatsApp::class)->jalankan($this->user))->toThrow(ValidationException::class, 'belum diaktifkan');

    Pengaturan::setel('wa_aktif', true);
    foreach (range(1, 3) as $_) {
        app(KirimPesanUjiWhatsApp::class)->jalankan($this->user);
    }
    expect(fn () => app(KirimPesanUjiWhatsApp::class)->jalankan($this->user))->toThrow(ValidationException::class, '3 kali');
    Queue::assertPushed(KirimPesanWhatsApp::class, 3);
});

it('menyediakan halaman preferensi di portal dan admin', function () {
    Filament::setCurrentPanel('portal');
    $this->actingAs($this->user);
    Livewire::test(ProfilPortal::class)->fillForm(['nomor_wa' => '081200000001', 'notif_wa_aktif' => true])->call('simpan');
    expect($this->user->refresh()->nomor_wa)->toBe('6281200000001');

    Filament::setCurrentPanel('admin');
    $admin = User::factory()->create(['nomor_wa' => null])->assignRole('admin-prodi');
    $this->actingAs($admin);
    Livewire::test(ProfilAdmin::class)->assertSuccessful();
});

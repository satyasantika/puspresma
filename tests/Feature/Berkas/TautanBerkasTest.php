<?php

use App\Contracts\PenyimpananBerkas;
use App\Enums\JenisTautan;
use App\Enums\PenyediaTautan;
use App\Enums\StatusCekTautan;
use App\Events\TautanBerkasTidakDapatDiakses;
use App\Jobs\PeriksaTautanBerkas;
use App\Models\Aktivitas;
use App\Models\Prodi;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Rules\TautanBerkasValid;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\Fixtures\PemilikUji;
use Tests\Fixtures\PemilikUjiPolicy;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    Relation::morphMap(['uji' => PemilikUji::class], merge: true);
    Gate::policy(PemilikUji::class, PemilikUjiPolicy::class);
    // Balasan HTTP dapat diganti per tes lewat $this->balasan (Http::fake berulang tidak menimpa stub lama).
    $this->balasan = fn () => Http::response('', 200);
    Http::fake(fn ($request) => ($this->balasan)($request));

    $this->pemilik = PemilikUji::find(Prodi::factory()->create()->id);
    $this->pengguna = User::factory()->create()->assignRole('mahasiswa');
    $this->simpan = fn (string $url, JenisTautan $jenis = JenisTautan::SertifikatPiagam, ?string $label = null) => app(PenyimpananBerkas::class)
        ->simpanTautan($this->pemilik, $jenis, $url, $label, $this->pengguna);
});

function pesanRule(string $url): ?string
{
    $v = Validator::make(['url' => $url], ['url' => [new TautanBerkasValid]]);

    return $v->fails() ? $v->errors()->first('url') : null;
}

it('menolak http, pemendek, domain asing, folder Drive, userinfo, dan tautan terlalu panjang', function (string $url, string $potongan) {
    expect(pesanRule($url))->toContain($potongan);
})->with([
    'http' => ['http://drive.google.com/file/d/ABC/view', 'https://'],
    'pemendek' => ['https://bit.ly/abc', 'Pemendek'],
    'domain asing' => ['https://example.com/file.pdf', 'Domain'],
    'folder' => ['https://drive.google.com/drive/folders/XYZ', 'bukan folder'],
    'folder akun' => ['https://drive.google.com/drive/u/0/folders/XYZ', 'bukan folder'],
    'userinfo' => ['https://drive.google.com@evil.com/file/d/ABC', 'tidak diizinkan'],
    'port aneh' => ['https://drive.google.com:8443/file/d/ABC', 'tidak diizinkan'],
    'terlalu panjang' => ['https://drive.google.com/file/d/'.str_repeat('a', 2100), 'terlalu panjang'],
]);

it('menerima tautan Drive, Docs, dan subdomain unsil.ac.id', function (string $url) {
    expect(pesanRule($url))->toBeNull();
})->with([
    'https://drive.google.com/file/d/ABC123/view?usp=sharing',
    'https://docs.google.com/document/d/DOC1/edit',
    'https://fkip.unsil.ac.id/berkas/sertifikat.pdf',
]);

it('menormalkan tautan dan mengekstrak id Drive dari semua pola', function (string $url, ?string $id) {
    $tautan = ($this->simpan)($url);

    expect($tautan->drive_file_id)->toBe($id);
})->with([
    'file/d' => ['https://drive.google.com/file/d/ABC/view?usp=sharing', 'ABC'],
    'open?id' => ['https://drive.google.com/open?id=XYZ_1-2', 'XYZ_1-2'],
    'uc?id' => ['https://drive.google.com/uc?id=UC9&export=download', 'UC9'],
    'document' => ['https://docs.google.com/document/d/DOC1/edit', 'DOC1'],
    'spreadsheets' => ['https://docs.google.com/spreadsheets/d/SHEET2/edit#gid=0', 'SHEET2'],
    'presentation' => ['https://docs.google.com/presentation/d/PPT3/edit', 'PPT3'],
    'unsil tanpa id' => ['https://fkip.unsil.ac.id/berkas/a.pdf', null],
]);

it('menyimpan url_normal tanpa usp, utm, dan fragmen serta menentukan penyedia', function () {
    $tautan = ($this->simpan)('https://DRIVE.google.com/file/d/ABC/view?usp=sharing&utm_source=wa&resourcekey=k#frag');

    expect($tautan->url_normal)->toBe('https://drive.google.com/file/d/ABC/view?resourcekey=k')
        ->and($tautan->penyedia)->toBe(PenyediaTautan::GoogleDrive)
        ->and(($this->simpan)('https://docs.google.com/document/d/D/edit')->penyedia)->toBe(PenyediaTautan::GoogleDocs)
        ->and(($this->simpan)('https://fkip.unsil.ac.id/a.pdf')->penyedia)->toBe(PenyediaTautan::Unsil);
});

it('membangun url pratinjau dan gambar hanya untuk Google Drive', function () {
    $penyimpanan = app(PenyimpananBerkas::class);
    $drive = ($this->simpan)('https://drive.google.com/file/d/ABC/view');
    $unsil = ($this->simpan)('https://fkip.unsil.ac.id/a.pdf');

    expect($penyimpanan->urlPratinjau($drive))->toBe('https://drive.google.com/file/d/ABC/preview')
        ->and($penyimpanan->urlGambar($drive))->toBe('https://lh3.googleusercontent.com/d/ABC')
        ->and($penyimpanan->urlBuka($drive))->toBe($drive->url)
        ->and($penyimpanan->urlPratinjau($unsil))->toBeNull()
        ->and($penyimpanan->urlGambar($unsil))->toBeNull();
});

it('menolak penyimpanan tautan tidak valid dengan pesan bahasa Indonesia', function () {
    expect(fn () => ($this->simpan)('http://example.com/x'))->toThrow(ValidationException::class, 'https://');
    expect(TautanBerkas::count())->toBe(0);
});

it('membatasi penyimpanan tautan 60 per jam per pengguna', function () {
    RateLimiter::hit('simpan-tautan:'.$this->pengguna->id, 3600);
    foreach (range(1, 59) as $_) {
        RateLimiter::hit('simpan-tautan:'.$this->pengguna->id, 3600);
    }

    expect(fn () => ($this->simpan)('https://drive.google.com/file/d/ABC/view'))->toThrow(ValidationException::class, 'Terlalu banyak');
});

it('menandai dapat_diakses bila 200 dan tidak dapat diakses bila 302 ke login Google atau timeout', function () {
    $tautan = ($this->simpan)('https://drive.google.com/file/d/ABC/view');
    expect($tautan->refresh()->status_cek)->toBe(StatusCekTautan::DapatDiakses)->and($tautan->dicek_pada)->not->toBeNull();

    $this->balasan = fn () => Http::response('', 302, ['Location' => 'https://accounts.google.com/ServiceLogin']);
    PeriksaTautanBerkas::dispatchSync($tautan);
    expect($tautan->refresh()->status_cek)->toBe(StatusCekTautan::TidakDapatDiakses);

    $this->balasan = fn () => throw new ConnectionException('timeout');
    PeriksaTautanBerkas::dispatchSync($tautan);
    expect($tautan->refresh()->status_cek)->toBe(StatusCekTautan::TidakDapatDiakses);
});

it('mencoba GET Range bila HEAD ditolak dan tidak memblokir penyimpanan saat cek gagal', function () {
    $this->balasan = fn ($request) => $request->method() === 'HEAD' ? Http::response('', 405) : Http::response('x', 206);
    $tautan = ($this->simpan)('https://drive.google.com/file/d/ABC/view');
    expect($tautan->refresh()->status_cek)->toBe(StatusCekTautan::DapatDiakses);

    $this->balasan = fn () => throw new ConnectionException('mati');
    $kedua = ($this->simpan)('https://drive.google.com/file/d/DEF/view');
    expect(TautanBerkas::count())->toBe(2)->and($kedua->refresh()->status_cek)->toBe(StatusCekTautan::TidakDapatDiakses);
});

it('mengirim event bila tautan berubah dari dapat diakses menjadi tidak dapat diakses', function () {
    $tautan = ($this->simpan)('https://drive.google.com/file/d/ABC/view');
    Event::fake([TautanBerkasTidakDapatDiakses::class]);
    $this->balasan = fn () => Http::response('', 404);

    PeriksaTautanBerkas::dispatchSync($tautan);

    Event::assertDispatched(TautanBerkasTidakDapatDiakses::class);
});

it('mereset status, menjadwalkan pemeriksaan ulang, dan mencatat url lama ke baru', function () {
    $tautan = ($this->simpan)('https://drive.google.com/file/d/ABC/view');
    Queue::fake();

    $tautan->update(['url' => 'https://drive.google.com/file/d/NEW/view']);

    expect($tautan->refresh()->status_cek)->toBe(StatusCekTautan::Belum)
        ->and($tautan->drive_file_id)->toBe('NEW');
    Queue::assertPushed(PeriksaTautanBerkas::class);
    $log = Aktivitas::where('log_name', 'tautan')->where('event', 'updated')->latest()->first();
    expect($log->attribute_changes['old']['url'])->toBe('https://drive.google.com/file/d/ABC/view')
        ->and($log->attribute_changes['attributes']['url'])->toBe('https://drive.google.com/file/d/NEW/view');
});

it('menaikkan versi cache galeri dan katalog untuk foto dan poster', function () {
    Cache::forget('galeri:versi');
    Cache::forget('katalog:versi');

    ($this->simpan)('https://drive.google.com/file/d/F1/view', JenisTautan::FotoPenyerahan);
    ($this->simpan)('https://drive.google.com/file/d/P1/view', JenisTautan::Poster);
    ($this->simpan)('https://drive.google.com/file/d/S1/view', JenisTautan::SertifikatPiagam);

    expect((int) Cache::get('galeri:versi'))->toBeGreaterThanOrEqual(1)->and((int) Cache::get('katalog:versi'))->toBeGreaterThanOrEqual(1);
});

it('membuka tautan lewat route tautan.buka: log tanpa isi dan redirect 302', function () {
    $tautan = ($this->simpan)('https://drive.google.com/file/d/ABC/view');
    $admin = User::factory()->create()->assignRole('admin-fakultas');

    $this->actingAs($admin)->get(route('tautan.buka', $tautan))->assertRedirect('https://drive.google.com/file/d/ABC/view');

    $log = Aktivitas::where('log_name', 'tautan')->where('event', 'dibuka')->first();
    expect($log->causer_id)->toBe($admin->id)
        ->and($log->subject_id)->toBe($tautan->id)
        ->and($log->properties->toArray())->toBe(['id' => $tautan->id, 'jenis' => 'sertifikat_piagam']);
});

it('menolak pembukaan tautan oleh pengguna tanpa hak pada pemilik dan mengalihkan tamu ke login', function () {
    $tautan = ($this->simpan)('https://drive.google.com/file/d/ABC/view');

    $this->actingAs($this->pengguna)->get(route('tautan.buka', $tautan))->assertForbidden();
    expect(Aktivitas::where('event', 'dibuka')->count())->toBe(0);

    auth()->logout();
    $this->get(route('tautan.buka', $tautan))->assertRedirect(route('login'));
});

it('melempar exception bila mode berkas bukan tautan', function () {
    config(['berkas.mode' => 'disk']);

    expect(fn () => app(PenyimpananBerkas::class))->toThrow(RuntimeException::class, 'belum didukung');
});

it('merender komponen tautan-berkas dan pratinjau-drive dengan placeholder', function () {
    $drive = ($this->simpan)('https://drive.google.com/file/d/ABC/view', JenisTautan::SertifikatPiagam, 'Sertifikat juara');
    $unsil = ($this->simpan)('https://fkip.unsil.ac.id/a.pdf');

    $html = Blade::render('<x-tautan-berkas :tautan="$t" /><x-pratinjau-drive :tautan="$t" />', ['t' => $drive]);
    expect($html)->toContain('target="_blank"')->toContain('rel="noopener noreferrer"')
        ->toContain(route('tautan.buka', $drive))->toContain('Sertifikat juara')
        ->toContain('https://drive.google.com/file/d/ABC/preview');

    expect(Blade::render('<x-pratinjau-drive :tautan="$t" />', ['t' => $unsil]))->toContain('Pratinjau tidak tersedia');
});

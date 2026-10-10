<?php

use App\Models\Kompetisi;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use App\Models\TautanBerkas;
use App\Rules\TautanBerkasValid;
use Database\Seeders\PengaturanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->seed([PeranDanIzinSeeder::class, PengaturanSeeder::class]);
    Http::fake(['*' => Http::response('', 200)]);
});

it('memasang header keamanan pada halaman publik dan halaman masuk panel', function (string $url) {
    $respons = $this->get($url)->assertOk();

    expect($respons->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($respons->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
        ->and($respons->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
        ->and($respons->headers->get('Permissions-Policy'))->toBe('camera=(), microphone=(), geolocation=()')
        ->and($respons->headers->get('Content-Security-Policy'))
        ->toContain("img-src 'self' data: https://lh3.googleusercontent.com")->toContain('frame-src https://drive.google.com https://docs.google.com')
        ->toContain("object-src 'none'")->toContain("frame-ancestors 'self'");
})->with(['/', '/kompetisi', '/admin/login', '/portal/login']);

it('memasang HSTS hanya di produksi', function () {
    $this->get('/')->assertHeaderMissing('Strict-Transport-Security');

    app()->detectEnvironment(fn () => 'production');
    $this->get('/')->assertHeader('Strict-Transport-Security');
});

it('menolak tautan berbahaya: javascript, data, host tiruan, userinfo, dan skema lain', function (string $url) {
    $v = Validator::make(['url' => $url], ['url' => [new TautanBerkasValid]]);

    expect($v->fails())->toBeTrue();
})->with([
    'javascript:alert(1)',
    'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
    'https://drive.google.com.evil.com/file/d/ABC/view',
    'https://evil.com/drive.google.com/file/d/ABC/view',
    'https://drive.google.com@evil.com/file/d/ABC/view',
    'https://evildrive.google.com/file/d/ABC/view',
    'ftp://drive.google.com/file/d/ABC/view',
    'https://127.0.0.1/file',
    'https://localhost/file',
]);

it('menghapus payload XSS dari deskripsi kompetisi dan merender aman di halaman publik', function () {
    $k = Kompetisi::factory()->terbit()->create(['deskripsi' => '<p>Halo</p><img src=x onerror=alert(1)><script>alert(2)</script><a href="javascript:alert(3)" onclick="x()">klik</a>']);

    expect($k->deskripsi)->not->toContain('onerror')->not->toContain('<script')->not->toContain('javascript:')->not->toContain('onclick');
    $this->get(route('kompetisi.show', $k->slug))->assertOk()->assertDontSee('onerror', false)->assertDontSee('<script>alert', false);
});

it('meng-escape deskripsi prestasi dan label tautan pada halaman publik dan komponen', function () {
    $pengaju = Mahasiswa::factory()->create();
    $p = Prestasi::factory()->terverifikasi()->for($pengaju, 'pengaju')->create(['deskripsi' => '<img src=x onerror=alert(1)>Teks', 'judul_karya' => '<script>alert(9)</script>']);
    PrestasiAnggota::where('prestasi_id', $p->id)->update(['setuju_publikasi' => true]);

    $html = $this->get(route('galeri.show', $p))->assertOk()->getContent();
    expect($html)->not->toContain('<img src=x onerror')->not->toContain('<script>alert(9)')->toContain('&lt;img src=x onerror=alert(1)&gt;Teks');

    $tautan = TautanBerkas::factory()->untukPrestasi($p)->sertifikat()->create(['label' => '<script>alert(5)</script>']);
    $render = Blade::render('<x-tautan-berkas :tautan="$t" />', ['t' => $tautan]);
    expect($render)->not->toContain('<script>alert(5)')->toContain('&lt;script&gt;alert(5)&lt;/script&gt;');
});

it('tidak memakai output Blade mentah kecuali untuk deskripsi kompetisi yang sudah disanitasi', function () {
    $pelanggar = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $berkas) {
        $path = $berkas->getPathname();
        if (str_ends_with($path, '.blade.php') && preg_match('/\{!!(?!\s*\$kompetisi->deskripsi\s*!!\})/', file_get_contents($path))) {
            $pelanggar[] = str_replace(resource_path('views').'/', '', $path);
        }
    }

    expect($pelanggar)->toBe([]);
});

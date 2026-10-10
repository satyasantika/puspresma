<?php

use Illuminate\Support\Facades\Process;
use Symfony\Component\Yaml\Yaml;

function alurCi(): array
{
    return Yaml::parseFile(base_path('.github/workflows/ci.yml'));
}

it('menjalankan deploy hanya setelah uji hijau dan hanya untuk push ke main', function () {
    $deploy = alurCi()['jobs']['deploy'];

    expect($deploy['needs'])->toBe(['uji'])
        ->and($deploy['if'])->toContain("github.event_name == 'push'")->toContain('refs/heads/main')
        ->and($deploy['concurrency']['cancel-in-progress'])->toBeFalse();
});

it('memanggil skrip rilis yang ikut repo, bukan skrip di luar repo, dan tidak ada workflow deploy paralel', function () {
    $skrip = alurCi()['jobs']['deploy']['steps'][0]['with']['script'];

    expect($skrip)->toContain('git reset --hard origin/main')->toContain('bash scripts/deploy.sh')
        ->not->toContain('~/docker-apps/deploy.sh')
        ->and(file_exists(base_path('.github/workflows/deploy.yml')))->toBeFalse();
});

it('menyediakan skrip rilis yang valid dan dapat dieksekusi', function () {
    $skrip = base_path('scripts/deploy.sh');

    expect(is_executable($skrip))->toBeTrue()
        ->and(Process::run(['bash', '-n', $skrip])->successful())->toBeTrue();
});

it('menolak argumen tak dikenal sebelum menyentuh docker', function () {
    $hasil = Process::run(['bash', base_path('scripts/deploy.sh'), '--tidak-ada']);

    expect($hasil->exitCode())->toBe(2)->and($hasil->errorOutput())->toContain('Argumen tidak dikenal');
});

it('mencadangkan sebelum membangun dan bermigrasi dengan image baru sebelum mengganti container', function () {
    $isi = file_get_contents(base_path('scripts/deploy.sh'));
    $urutan = fn (string $kata): int => (int) strpos($isi, $kata);

    // Pemanggilan dalam fungsi rilis(): cadangan → build → migrate → up -d.
    $rilis = substr($isi, (int) strpos($isi, "rilis() {\n"));
    $pos = fn (string $kata): int => (int) strpos($rilis, $kata);

    expect($pos("\n  cadangan\n"))->toBeLessThan($pos('"${COMPOSE[@]}" build'))
        ->and($pos('"${COMPOSE[@]}" build'))->toBeLessThan($pos('artisan migrate --force'))
        ->and($pos('artisan migrate --force'))->toBeLessThan($pos('up -d --remove-orphans'))
        ->and($urutan('tunggu_sehat'))->toBeGreaterThan(0);
});

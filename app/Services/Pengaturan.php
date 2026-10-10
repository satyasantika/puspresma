<?php

namespace App\Services;

use App\Models\Pengaturan as ModelPengaturan;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/** Pengaturan sistem berbasis kunci-nilai; seluruh tabel di-cache selamanya dan dibuang saat setel(). */
class Pengaturan
{
    public const KUNCI_CACHE = 'pengaturan:semua';

    /**
     * Nilai pengaturan sesuai tipenya (bool/int/decimal sebagai string numerik/json/string).
     */
    public static function ambil(string $kunci, mixed $bawaan = null): mixed
    {
        $baris = self::semua()[$kunci] ?? null;

        if ($baris === null || $baris['nilai'] === null || $baris['nilai'] === '') {
            return $bawaan;
        }

        return self::cast($baris['tipe'], $baris['nilai']);
    }

    /** Menyimpan nilai, mencatat activitylog (lama → baru), dan membuang cache. */
    public static function setel(string $kunci, mixed $nilai): void
    {
        $model = ModelPengaturan::query()->where('kunci', $kunci)->first()
            ?? throw new InvalidArgumentException("Pengaturan '{$kunci}' tidak dikenal.");

        $model->update(['nilai' => self::serialisasi($model->tipe, $nilai)]);

        Cache::forget(self::KUNCI_CACHE);

        // Pengaturan yang memengaruhi isi galeri publik membuang cache galeri.
        if (in_array($kunci, ['galeri_aktif', 'teks_persetujuan_publikasi'], true)) {
            Cache::increment('galeri:versi');
        }
    }

    /** @return array<string, array{nilai: ?string, tipe: string}> */
    private static function semua(): array
    {
        return Cache::rememberForever(self::KUNCI_CACHE, fn () => ModelPengaturan::query()
            ->get(['kunci', 'nilai', 'tipe'])
            ->mapWithKeys(fn ($m) => [$m->kunci => ['nilai' => $m->nilai, 'tipe' => $m->tipe]])
            ->all());
    }

    private static function cast(string $tipe, string $nilai): mixed
    {
        return match ($tipe) {
            'bool' => in_array(strtolower($nilai), ['1', 'true', 'ya', 'on'], true),
            'int' => (int) $nilai,
            'json' => json_decode($nilai, true),
            default => $nilai,
        };
    }

    private static function serialisasi(string $tipe, mixed $nilai): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return match ($tipe) {
            'bool' => filter_var($nilai, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'json' => json_encode($nilai, JSON_UNESCAPED_UNICODE),
            default => (string) $nilai,
        };
    }
}

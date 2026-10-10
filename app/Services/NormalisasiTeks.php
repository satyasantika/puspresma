<?php

namespace App\Services;

use Illuminate\Support\Str;

/** Normalisasi teks untuk pembandingan (deteksi duplikat). */
class NormalisasiTeks
{
    /** Kata umum yang tidak membedakan kegiatan. */
    private const KATA_UMUM = ['lomba', 'kompetisi', 'tingkat', 'nasional', 'internasional', 'tahun'];

    /** Huruf kecil, tanpa tanda baca, tanpa angka tahun 4 digit dan kata umum, spasi dirapikan. */
    public static function kegiatan(string $nama): string
    {
        $teks = Str::of(Str::lower($nama))
            ->replaceMatches('/[^\p{L}\p{N}\s]+/u', ' ')
            ->replaceMatches('/\b(19|20)\d{2}\b/u', ' ')
            ->explode(' ')
            ->filter(fn (string $kata) => $kata !== '' && ! in_array($kata, self::KATA_UMUM, true))
            ->implode(' ');

        return trim($teks);
    }
}

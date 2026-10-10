<?php

namespace App\Services\Ekspor;

use App\Models\User;
use Generator;

/** Definisi satu laporan: judul, kolom, jumlah baris, dan baris (streaming). Penulisan berkas oleh PenulisBerkas. */
abstract class LaporanEkspor
{
    /** Kode laporan di berkas_ekspor.jenis (≤ 30 karakter). */
    abstract public function jenis(): string;

    abstract public function judul(): string;

    /** Permission yang dibutuhkan pemohon. */
    public function izin(): string
    {
        return 'laporan.ekspor';
    }

    /**
     * @return list<string>
     */
    abstract public function header(User $pengguna): array;

    /** @param  array<string, mixed>  $filter */
    abstract public function jumlah(array $filter, User $pengguna): int;

    /**
     * @param  array<string, mixed>  $filter
     * @return Generator<int, list<string|int|float|null>>
     */
    abstract public function baris(array $filter, User $pengguna): Generator;

    /**
     * Format yang didukung laporan.
     *
     * @return list<string>
     */
    public function format(): array
    {
        return ['xlsx', 'pdf'];
    }

    /**
     * Ringkasan filter untuk kop PDF.
     *
     * @param  array<string, mixed>  $filter
     */
    public function keteranganFilter(array $filter): string
    {
        return collect($filter)->filter(fn ($v) => filled($v))->map(fn ($v, $k) => str_replace('_', ' ', $k).': '.(is_array($v) ? implode(', ', $v) : $v))->implode(' · ') ?: 'Tanpa filter';
    }
}

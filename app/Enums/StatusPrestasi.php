<?php

namespace App\Enums;

enum StatusPrestasi: string
{
    case Draf = 'draf';
    case Diajukan = 'diajukan';
    case LolosProdi = 'lolos_prodi';
    case PerluPerbaikan = 'perlu_perbaikan';
    case Terverifikasi = 'terverifikasi';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Diajukan => 'Diajukan',
            self::LolosProdi => 'Lolos tahap prodi',
            self::PerluPerbaikan => 'Perlu perbaikan',
            self::Terverifikasi => 'Terverifikasi',
            self::Ditolak => 'Ditolak',
        };
    }

    /** Warna badge Filament. */
    public function warna(): string
    {
        return match ($this) {
            self::Draf => 'gray',
            self::Diajukan => 'info',
            self::LolosProdi => 'primary',
            self::PerluPerbaikan => 'warning',
            self::Terverifikasi => 'success',
            self::Ditolak => 'danger',
        };
    }

    /** Pengaju boleh mengubah data/tautan hanya pada status ini (BR-03, BR-14). */
    public function bolehDiubahMahasiswa(): bool
    {
        return in_array($this, [self::Draf, self::PerluPerbaikan], true);
    }

    /** @return list<self> */
    public function tujuanSah(): array
    {
        return match ($this) {
            self::Draf => [self::Diajukan],
            self::Diajukan => [self::Draf, self::LolosProdi, self::PerluPerbaikan, self::Terverifikasi, self::Ditolak],
            self::LolosProdi => [self::PerluPerbaikan, self::Terverifikasi, self::Ditolak],
            self::PerluPerbaikan => [self::Diajukan],
            self::Terverifikasi => [self::PerluPerbaikan],
            self::Ditolak => [],
        };
    }

    /** Tabel transisi BR-07. */
    public function bolehKe(self $ke): bool
    {
        return in_array($ke, $this->tujuanSah(), true);
    }

    /** Transisi yang mewajibkan catatan minimal 10 karakter (BR-08). */
    public function wajibCatatanKe(self $ke): bool
    {
        return in_array($ke, [self::PerluPerbaikan, self::Ditolak], true);
    }
}

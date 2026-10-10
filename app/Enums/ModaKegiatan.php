<?php

namespace App\Enums;

enum ModaKegiatan: string
{
    case Luring = 'luring';
    case Daring = 'daring';
    case Hibrida = 'hibrida';

    public function label(): string
    {
        return match ($this) {
            self::Luring => 'Luring',
            self::Daring => 'Daring',
            self::Hibrida => 'Hibrida',
        };
    }

    /** Lokasi wajib diisi bila ada pertemuan fisik. */
    public function perluLokasi(): bool
    {
        return $this !== self::Daring;
    }
}

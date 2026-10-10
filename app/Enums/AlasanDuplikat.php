<?php

namespace App\Enums;

enum AlasanDuplikat: string
{
    case KegiatanMirip = 'kegiatan_mirip';
    case TautanSama = 'tautan_sama';

    public function label(): string
    {
        return match ($this) {
            self::KegiatanMirip => 'Kegiatan mirip (anggota sama)',
            self::TautanSama => 'Tautan bukti sama',
        };
    }
}

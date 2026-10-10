<?php

namespace App\Enums;

enum PeranAnggota: string
{
    case Ketua = 'ketua';
    case Anggota = 'anggota';

    public function label(): string
    {
        return match ($this) {
            self::Ketua => 'Ketua',
            self::Anggota => 'Anggota',
        };
    }
}

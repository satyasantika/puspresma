<?php

namespace App\Enums;

enum StatusSurat: string
{
    case Aktif = 'aktif';
    case Dicabut = 'dicabut';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Dicabut => 'Dicabut',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Aktif => 'success',
            self::Dicabut => 'danger',
        };
    }
}

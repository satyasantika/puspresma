<?php

namespace App\Enums;

enum StatusKompetisi: string
{
    case Draf = 'draf';
    case Terbit = 'terbit';
    case Diarsipkan = 'diarsipkan';

    public function label(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Terbit => 'Terbit',
            self::Diarsipkan => 'Diarsipkan',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Draf => 'gray',
            self::Terbit => 'success',
            self::Diarsipkan => 'warning',
        };
    }
}

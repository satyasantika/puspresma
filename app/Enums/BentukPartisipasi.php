<?php

namespace App\Enums;

enum BentukPartisipasi: string
{
    case Individu = 'individu';
    case Kelompok = 'kelompok';

    public function label(): string
    {
        return match ($this) {
            self::Individu => 'Individu',
            self::Kelompok => 'Kelompok/tim',
        };
    }
}

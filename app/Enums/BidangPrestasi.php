<?php

namespace App\Enums;

enum BidangPrestasi: string
{
    case Akademik = 'akademik';
    case NonAkademik = 'non_akademik';

    public function label(): string
    {
        return match ($this) {
            self::Akademik => 'Akademik',
            self::NonAkademik => 'Non-akademik',
        };
    }
}

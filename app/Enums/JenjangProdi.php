<?php

namespace App\Enums;

enum JenjangProdi: string
{
    case S1 = 'S1';
    case S2 = 'S2';
    case S3 = 'S3';
    case Profesi = 'Profesi';

    public function label(): string
    {
        return match ($this) {
            self::S1 => 'S1',
            self::S2 => 'S2',
            self::S3 => 'S3',
            self::Profesi => 'Profesi',
        };
    }
}

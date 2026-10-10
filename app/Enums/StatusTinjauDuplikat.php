<?php

namespace App\Enums;

enum StatusTinjauDuplikat: string
{
    case BelumDitinjau = 'belum_ditinjau';
    case BukanDuplikat = 'bukan_duplikat';
    case Duplikat = 'duplikat';

    public function label(): string
    {
        return match ($this) {
            self::BelumDitinjau => 'Belum ditinjau',
            self::BukanDuplikat => 'Bukan duplikat',
            self::Duplikat => 'Duplikat',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::BelumDitinjau => 'warning',
            self::BukanDuplikat => 'success',
            self::Duplikat => 'danger',
        };
    }
}

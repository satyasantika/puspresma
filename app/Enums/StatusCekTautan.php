<?php

namespace App\Enums;

enum StatusCekTautan: string
{
    case Belum = 'belum';
    case DapatDiakses = 'dapat_diakses';
    case TidakDapatDiakses = 'tidak_dapat_diakses';

    public function label(): string
    {
        return match ($this) {
            self::Belum => 'Belum diperiksa',
            self::DapatDiakses => 'Dapat diakses',
            self::TidakDapatDiakses => 'Tidak dapat diakses',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Belum => 'gray',
            self::DapatDiakses => 'success',
            self::TidakDapatDiakses => 'danger',
        };
    }
}

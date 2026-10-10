<?php

namespace App\Enums;

enum StatusBerkasEkspor: string
{
    case Antre = 'antre';
    case Proses = 'proses';
    case Siap = 'siap';
    case Gagal = 'gagal';
    case Kedaluwarsa = 'kedaluwarsa';

    public function label(): string
    {
        return match ($this) {
            self::Antre => 'Antre',
            self::Proses => 'Diproses',
            self::Siap => 'Siap diunduh',
            self::Gagal => 'Gagal',
            self::Kedaluwarsa => 'Kedaluwarsa',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Antre => 'gray',
            self::Proses => 'warning',
            self::Siap => 'success',
            self::Gagal => 'danger',
            self::Kedaluwarsa => 'gray',
        };
    }
}

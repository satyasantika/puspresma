<?php

namespace App\Enums;

enum StatusImpor: string
{
    case Antre = 'antre';
    case Proses = 'proses';
    case Selesai = 'selesai';
    case Gagal = 'gagal';

    public function label(): string
    {
        return match ($this) {
            self::Antre => 'Antre',
            self::Proses => 'Diproses',
            self::Selesai => 'Selesai',
            self::Gagal => 'Gagal',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Antre => 'gray',
            self::Proses => 'warning',
            self::Selesai => 'success',
            self::Gagal => 'danger',
        };
    }
}

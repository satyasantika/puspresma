<?php

namespace App\Enums;

enum StatusKonfirmasiPembimbing: string
{
    case TidakAda = 'tidak_ada';
    case Menunggu = 'menunggu';
    case Dikonfirmasi = 'dikonfirmasi';
    case Disangkal = 'disangkal';

    public function label(): string
    {
        return match ($this) {
            self::TidakAda => 'Tidak ada pembimbing',
            self::Menunggu => 'Menunggu konfirmasi',
            self::Dikonfirmasi => 'Dikonfirmasi',
            self::Disangkal => 'Disangkal',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::TidakAda => 'gray',
            self::Menunggu => 'warning',
            self::Dikonfirmasi => 'success',
            self::Disangkal => 'danger',
        };
    }
}

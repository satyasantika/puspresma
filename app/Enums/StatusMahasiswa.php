<?php

namespace App\Enums;

enum StatusMahasiswa: string
{
    case Aktif = 'aktif';
    case Cuti = 'cuti';
    case Lulus = 'lulus';
    case Keluar = 'keluar';
    case Nonaktif = 'nonaktif';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Cuti => 'Cuti',
            self::Lulus => 'Lulus',
            self::Keluar => 'Keluar',
            self::Nonaktif => 'Nonaktif',
        };
    }

    /** Status yang boleh masuk portal (BR-20). */
    public function bolehMasukPortal(): bool
    {
        return in_array($this, [self::Aktif, self::Cuti], true);
    }
}

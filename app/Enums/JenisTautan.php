<?php

namespace App\Enums;

enum JenisTautan: string
{
    case SertifikatPiagam = 'sertifikat_piagam';
    case FotoPenyerahan = 'foto_penyerahan';
    case SuratTugas = 'surat_tugas';
    case Lainnya = 'lainnya';
    case Poster = 'poster';

    public function label(): string
    {
        return match ($this) {
            self::SertifikatPiagam => 'Sertifikat/piagam',
            self::FotoPenyerahan => 'Foto penyerahan',
            self::SuratTugas => 'Surat tugas',
            self::Lainnya => 'Lainnya',
            self::Poster => 'Poster',
        };
    }
}

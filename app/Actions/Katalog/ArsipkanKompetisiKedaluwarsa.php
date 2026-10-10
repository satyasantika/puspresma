<?php

namespace App\Actions\Katalog;

use App\Enums\StatusKompetisi;
use App\Models\Kompetisi;

/** Mengarsipkan kompetisi terbit yang tenggatnya lewat; dipanggil scheduler (F7.3). */
class ArsipkanKompetisiKedaluwarsa
{
    /** @return int jumlah kompetisi yang diarsipkan */
    public function jalankan(): int
    {
        $jumlah = 0;

        Kompetisi::query()->terbit()->where('tenggat_pendaftaran', '<', now())->each(function (Kompetisi $k) use (&$jumlah): void {
            $k->update(['status' => StatusKompetisi::Diarsipkan]);
            $jumlah++;
        });

        return $jumlah;
    }
}

<?php

namespace App\Actions\Katalog;

use App\Enums\StatusKompetisi;
use App\Models\Kompetisi;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/** terbit → diarsipkan secara manual. */
class ArsipkanKompetisi
{
    public function jalankan(Kompetisi $kompetisi, User $pelaku): Kompetisi
    {
        Gate::forUser($pelaku)->authorize('arsipkan', $kompetisi);

        $kompetisi->update(['status' => StatusKompetisi::Diarsipkan]);

        return $kompetisi;
    }
}

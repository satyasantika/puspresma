<?php

namespace App\Actions\Katalog;

use App\Enums\StatusKompetisi;
use App\Events\KompetisiDiterbitkan;
use App\Models\Kompetisi;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** draf → terbit; ditolak bila tenggat pendaftaran sudah lewat (BR-23). */
class TerbitkanKompetisi
{
    public function jalankan(Kompetisi $kompetisi, User $pelaku): Kompetisi
    {
        Gate::forUser($pelaku)->authorize('terbitkan', $kompetisi);

        if ($kompetisi->status !== StatusKompetisi::Draf) {
            throw ValidationException::withMessages(['status' => 'Hanya kompetisi draf yang dapat diterbitkan.']);
        }

        if ($kompetisi->tenggat_pendaftaran->isPast()) {
            throw ValidationException::withMessages(['tenggat_pendaftaran' => 'Tenggat pendaftaran sudah lewat; perbarui tenggat sebelum menerbitkan.']);
        }

        $kompetisi->update(['status' => StatusKompetisi::Terbit, 'diterbitkan_at' => now()]);
        KompetisiDiterbitkan::dispatch($kompetisi);

        return $kompetisi;
    }
}

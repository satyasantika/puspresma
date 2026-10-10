<?php

namespace App\Actions\Bukti;

use App\Models\TautanBerkas;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/** Menghapus (soft delete) tautan bukti selama prestasi masih boleh diubah. */
class HapusTautanBukti
{
    public function jalankan(TautanBerkas $tautan, User $pelaku): void
    {
        Gate::forUser($pelaku)->authorize('delete', $tautan);

        $tautan->delete();
    }
}

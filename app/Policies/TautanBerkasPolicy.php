<?php

namespace App\Policies;

use App\Models\TautanBerkas;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/** Dasar: tautan dapat dilihat oleh siapa pun yang boleh melihat pemiliknya (Prestasi/Kompetisi). */
class TautanBerkasPolicy
{
    public function view(User $pelaku, TautanBerkas $tautan): bool
    {
        $pemilik = $tautan->pemilik;

        return $pemilik !== null && Gate::forUser($pelaku)->allows('view', $pemilik);
    }
}

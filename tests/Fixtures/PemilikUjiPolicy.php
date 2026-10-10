<?php

namespace Tests\Fixtures;

use App\Models\User;

class PemilikUjiPolicy
{
    public function view(User $pelaku, PemilikUji $pemilik): bool
    {
        return $pelaku->can('prestasi.lihat-semua');
    }
}

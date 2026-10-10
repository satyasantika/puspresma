<?php

namespace App\Policies;

use App\Models\BerkasEkspor;
use App\Models\User;

/** Berkas ekspor hanya dapat dilihat/diunduh pemohonnya (super-admin lewat Gate::before) — BR-25. */
class BerkasEksporPolicy
{
    public function viewAny(User $pelaku): bool
    {
        return $pelaku->canAny(['laporan.ekspor', 'simkatmawa.ekspor']);
    }

    public function view(User $pelaku, BerkasEkspor $berkas): bool
    {
        return $berkas->user_id === $pelaku->getKey();
    }

    public function unduh(User $pelaku, BerkasEkspor $berkas): bool
    {
        return $this->view($pelaku, $berkas);
    }
}

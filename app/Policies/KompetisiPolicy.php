<?php

namespace App\Policies;

use App\Models\Kompetisi;
use App\Models\User;

class KompetisiPolicy
{
    public function viewAny(User $pelaku): bool
    {
        return $pelaku->hasAnyRole(User::PERAN_PANEL['admin']);
    }

    public function view(User $pelaku, Kompetisi $kompetisi): bool
    {
        return $this->viewAny($pelaku);
    }

    public function create(User $pelaku): bool
    {
        return $pelaku->canAny(['kompetisi.kelola', 'kompetisi.kelola-prodi']);
    }

    /** Admin-fakultas penuh; admin-prodi hanya kompetisi yang sasarannya tepat satu: prodinya sendiri (BR-23). */
    public function update(User $pelaku, Kompetisi $kompetisi): bool
    {
        if ($pelaku->can('kompetisi.kelola')) {
            return true;
        }

        if (! $pelaku->can('kompetisi.kelola-prodi') || $pelaku->prodi_id === null) {
            return false;
        }

        $sasaran = $kompetisi->prodiSasaran()->pluck('prodi.id');

        return $sasaran->count() === 1 && $sasaran->first() === $pelaku->prodi_id;
    }

    public function delete(User $pelaku, Kompetisi $kompetisi): bool
    {
        return $this->update($pelaku, $kompetisi);
    }

    public function terbitkan(User $pelaku, Kompetisi $kompetisi): bool
    {
        return $this->update($pelaku, $kompetisi);
    }

    public function arsipkan(User $pelaku, Kompetisi $kompetisi): bool
    {
        return $this->update($pelaku, $kompetisi);
    }
}

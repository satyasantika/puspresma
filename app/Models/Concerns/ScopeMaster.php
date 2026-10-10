<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/** Scope bersama untuk tabel master klasifikasi (is_active + urutan, nama). */
trait ScopeMaster
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_active'), true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeTerurut(Builder $query): Builder
    {
        if (in_array('urutan', $this->getFillable(), true)) {
            $query->orderBy($query->qualifyColumn('urutan'));
        }

        return $query->orderBy($query->qualifyColumn('nama'));
    }
}

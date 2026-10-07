<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ProdiScope
{
    /**
     * Batasi query ke prodi pengguna bila ia tidak boleh melihat semua (mis. admin-prodi).
     * Bila prodi_id pengguna kosong, hasilnya kosong.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function untuk(User $user, Builder $query, string $kolom = 'prodi_id'): Builder
    {
        if ($user->can('prestasi.lihat-semua')) {
            return $query;
        }

        if ($user->prodi_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($query->qualifyColumn($kolom), $user->prodi_id);
    }
}

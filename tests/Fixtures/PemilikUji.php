<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Pemilik tautan untuk uji (tidak bergantung pada Prestasi/Kompetisi); memakai tabel prodi yang sudah ada. */
class PemilikUji extends Model
{
    use HasUuids;

    protected $table = 'prodi';

    protected $guarded = [];
}

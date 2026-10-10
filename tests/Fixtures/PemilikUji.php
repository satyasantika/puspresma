<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Pemilik tautan untuk uji (Prestasi/Kompetisi belum ada di F3); memakai tabel prodi yang sudah ada. */
class PemilikUji extends Model
{
    use HasUuids;

    protected $table = 'prodi';

    protected $guarded = [];
}

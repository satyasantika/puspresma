<?php

namespace App\Events;

use App\Models\Prestasi;
use Illuminate\Foundation\Events\Dispatchable;

/** Kandidat duplikat baru ditemukan untuk prestasi yang diajukan (notifikasi di F7.1). */
class KandidatDuplikatDitemukan
{
    use Dispatchable;

    public function __construct(public Prestasi $prestasi, public int $jumlahBaru) {}
}

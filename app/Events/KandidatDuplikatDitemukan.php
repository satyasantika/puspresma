<?php

namespace App\Events;

use App\Models\Prestasi;
use Illuminate\Foundation\Events\Dispatchable;

/** Kandidat duplikat baru ditemukan untuk prestasi yang diajukan (memicu notifikasi). */
class KandidatDuplikatDitemukan
{
    use Dispatchable;

    public function __construct(public Prestasi $prestasi, public int $jumlahBaru) {}
}

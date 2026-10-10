<?php

namespace App\Events;

use App\Models\Prestasi;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/** Dosen pembimbing mengonfirmasi/menyangkal (notifikasi admin dan pengaju di F7.1). */
class PembimbingMenjawab
{
    use Dispatchable;

    public function __construct(public Prestasi $prestasi, public User $dosen, public bool $dikonfirmasi) {}
}

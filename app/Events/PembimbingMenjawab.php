<?php

namespace App\Events;

use App\Models\Prestasi;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/** Dosen pembimbing mengonfirmasi/menyangkal (memicu notifikasi admin dan pengaju). */
class PembimbingMenjawab
{
    use Dispatchable;

    public function __construct(public Prestasi $prestasi, public User $dosen, public bool $dikonfirmasi) {}
}

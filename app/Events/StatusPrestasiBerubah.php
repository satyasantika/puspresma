<?php

namespace App\Events;

use App\Enums\StatusPrestasi;
use App\Models\Prestasi;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Status prestasi berubah (listener: duplikat F5.2, notifikasi F7.1, cache galeri/statistik). */
class StatusPrestasiBerubah implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public Prestasi $prestasi, public ?StatusPrestasi $dari, public StatusPrestasi $ke) {}
}

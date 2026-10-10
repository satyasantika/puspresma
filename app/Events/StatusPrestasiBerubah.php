<?php

namespace App\Events;

use App\Enums\StatusPrestasi;
use App\Models\Prestasi;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Status prestasi berubah (listener: deteksi duplikat, notifikasi, cache galeri/statistik). */
class StatusPrestasiBerubah implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public Prestasi $prestasi, public ?StatusPrestasi $dari, public StatusPrestasi $ke) {}
}

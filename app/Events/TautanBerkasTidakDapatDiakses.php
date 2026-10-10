<?php

namespace App\Events;

use App\Models\TautanBerkas;
use Illuminate\Foundation\Events\Dispatchable;

/** Tautan yang sebelumnya dapat diakses kini tidak dapat diakses (notifikasi pemilik di F7.1). */
class TautanBerkasTidakDapatDiakses
{
    use Dispatchable;

    public function __construct(public TautanBerkas $tautan) {}
}

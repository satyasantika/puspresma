<?php

namespace App\Events;

use App\Models\Kompetisi;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class KompetisiDiterbitkan implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public Kompetisi $kompetisi) {}
}

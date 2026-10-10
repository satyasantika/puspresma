<?php

namespace App\Observers;

use App\Services\PenghitungPoin;
use Illuminate\Support\Facades\Cache;

/** Membuang cache bobot poin setiap kali bobot berubah. */
class BobotPoinObserver
{
    public function saved(): void
    {
        Cache::forget(PenghitungPoin::KUNCI_CACHE);
    }

    public function deleted(): void
    {
        Cache::forget(PenghitungPoin::KUNCI_CACHE);
    }
}

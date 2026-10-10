<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;

/** Setiap perubahan kompetisi membuang cache katalog (pola versi). */
class KompetisiObserver
{
    public function saved(): void
    {
        Cache::increment('katalog:versi');
    }

    public function deleted(): void
    {
        Cache::increment('katalog:versi');
    }

    public function restored(): void
    {
        Cache::increment('katalog:versi');
    }
}

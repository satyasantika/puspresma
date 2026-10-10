<?php

namespace App\Listeners;

use App\Enums\StatusPrestasi;
use App\Events\StatusPrestasiBerubah;
use App\Jobs\HitungPoinPrestasiJob;

class HitungPoinSaatTerverifikasi
{
    public function handle(StatusPrestasiBerubah $event): void
    {
        if ($event->ke === StatusPrestasi::Terverifikasi) {
            HitungPoinPrestasiJob::dispatch($event->prestasi);
        }
    }
}

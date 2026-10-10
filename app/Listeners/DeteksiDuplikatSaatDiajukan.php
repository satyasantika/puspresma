<?php

namespace App\Listeners;

use App\Enums\StatusPrestasi;
use App\Events\StatusPrestasiBerubah;
use App\Jobs\DeteksiDuplikatPrestasi;

class DeteksiDuplikatSaatDiajukan
{
    public function handle(StatusPrestasiBerubah $event): void
    {
        if ($event->ke === StatusPrestasi::Diajukan) {
            DeteksiDuplikatPrestasi::dispatch($event->prestasi);
        }
    }
}

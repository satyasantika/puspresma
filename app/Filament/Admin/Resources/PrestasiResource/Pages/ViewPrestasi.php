<?php

namespace App\Filament\Admin\Resources\PrestasiResource\Pages;

use App\Filament\Admin\Resources\PrestasiResource;
use App\Filament\Admin\Resources\PrestasiResource\Actions\AksiVerifikasi;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPrestasi extends ViewRecord
{
    protected static string $resource = PrestasiResource::class;

    protected function getHeaderActions(): array
    {
        return [...AksiVerifikasi::semua(), EditAction::make()];
    }
}

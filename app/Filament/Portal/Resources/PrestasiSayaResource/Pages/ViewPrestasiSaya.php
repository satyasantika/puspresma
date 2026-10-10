<?php

namespace App\Filament\Portal\Resources\PrestasiSayaResource\Pages;

use App\Filament\Portal\Resources\PrestasiSayaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPrestasiSaya extends ViewRecord
{
    protected static string $resource = PrestasiSayaResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}

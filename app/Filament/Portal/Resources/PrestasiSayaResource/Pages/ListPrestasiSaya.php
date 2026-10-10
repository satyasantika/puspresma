<?php

namespace App\Filament\Portal\Resources\PrestasiSayaResource\Pages;

use App\Filament\Portal\Resources\PrestasiSayaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrestasiSaya extends ListRecords
{
    protected static string $resource = PrestasiSayaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Catat prestasi')];
    }
}

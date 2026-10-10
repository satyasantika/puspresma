<?php

namespace App\Filament\Admin\Resources\KompetisiResource\Pages;

use App\Filament\Admin\Resources\KompetisiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKompetisi extends ListRecords
{
    protected static string $resource = KompetisiResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

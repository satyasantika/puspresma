<?php

namespace App\Filament\Admin\Resources\KategoriLombaResource\Pages;

use App\Filament\Admin\Resources\KategoriLombaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageKategoriLomba extends ManageRecords
{
    protected static string $resource = KategoriLombaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

<?php

namespace App\Filament\Admin\Resources\TingkatResource\Pages;

use App\Filament\Admin\Resources\TingkatResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTingkat extends ManageRecords
{
    protected static string $resource = TingkatResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

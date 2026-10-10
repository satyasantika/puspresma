<?php

namespace App\Filament\Admin\Resources\ProdiResource\Pages;

use App\Filament\Admin\Resources\ProdiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageProgramStudi extends ManageRecords
{
    protected static string $resource = ProdiResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

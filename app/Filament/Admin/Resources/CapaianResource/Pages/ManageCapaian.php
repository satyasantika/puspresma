<?php

namespace App\Filament\Admin\Resources\CapaianResource\Pages;

use App\Filament\Admin\Resources\CapaianResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCapaian extends ManageRecords
{
    protected static string $resource = CapaianResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

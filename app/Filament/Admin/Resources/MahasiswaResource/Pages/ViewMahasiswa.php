<?php

namespace App\Filament\Admin\Resources\MahasiswaResource\Pages;

use App\Filament\Admin\Resources\MahasiswaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMahasiswa extends ViewRecord
{
    protected static string $resource = MahasiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}

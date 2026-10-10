<?php

namespace App\Filament\Admin\Resources\MahasiswaResource\Pages;

use App\Filament\Admin\Resources\MahasiswaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMahasiswa extends ListRecords
{
    protected static string $resource = MahasiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

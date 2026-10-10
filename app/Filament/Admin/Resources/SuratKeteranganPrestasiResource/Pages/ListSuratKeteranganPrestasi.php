<?php

namespace App\Filament\Admin\Resources\SuratKeteranganPrestasiResource\Pages;

use App\Filament\Admin\Resources\SuratKeteranganPrestasiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSuratKeteranganPrestasi extends ListRecords
{
    protected static string $resource = SuratKeteranganPrestasiResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Terbitkan surat')];
    }
}

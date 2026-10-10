<?php

namespace App\Filament\Admin\Resources\JenisKegiatanResource\Pages;

use App\Filament\Admin\Resources\JenisKegiatanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageJenisKegiatan extends ManageRecords
{
    protected static string $resource = JenisKegiatanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

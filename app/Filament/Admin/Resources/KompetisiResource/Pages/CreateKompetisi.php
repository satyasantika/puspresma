<?php

namespace App\Filament\Admin\Resources\KompetisiResource\Pages;

use App\Actions\Katalog\SimpanKompetisi;
use App\Filament\Admin\Resources\KompetisiResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateKompetisi extends CreateRecord
{
    protected static string $resource = KompetisiResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SimpanKompetisi::class)->jalankan(null, $data, auth()->user());
    }
}

<?php

namespace App\Filament\Portal\Resources\PrestasiSayaResource\Pages;

use App\Actions\Prestasi\SimpanPrestasi;
use App\Filament\Portal\Resources\PrestasiSayaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePrestasiSaya extends CreateRecord
{
    protected static string $resource = PrestasiSayaResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SimpanPrestasi::class)->jalankan(null, $data, auth()->user());
    }
}

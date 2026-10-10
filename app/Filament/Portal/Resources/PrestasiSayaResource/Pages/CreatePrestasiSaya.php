<?php

namespace App\Filament\Portal\Resources\PrestasiSayaResource\Pages;

use App\Filament\Portal\Resources\PrestasiSayaResource;
use App\Filament\Portal\Resources\PrestasiSayaResource\Pages\Concerns\SimpanRelasiPrestasi;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePrestasiSaya extends CreateRecord
{
    use SimpanRelasiPrestasi;

    protected static string $resource = PrestasiSayaResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->simpanSemua(null, $data);
    }
}

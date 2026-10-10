<?php

namespace App\Filament\Admin\Resources\PrestasiResource\Pages;

use App\Filament\Admin\Resources\PrestasiResource;
use App\Filament\Shared\Concerns\SimpanRelasiPrestasi;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/** Input atas nama mahasiswa (data lama/arsip); status awal draf, lalu diajukan/diverifikasi lewat transisi. */
class CreatePrestasi extends CreateRecord
{
    use SimpanRelasiPrestasi;

    protected static string $resource = PrestasiResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->simpanSemua(null, $data);
    }
}

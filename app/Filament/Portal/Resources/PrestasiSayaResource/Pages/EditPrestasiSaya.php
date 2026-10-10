<?php

namespace App\Filament\Portal\Resources\PrestasiSayaResource\Pages;

use App\Actions\Prestasi\SimpanPrestasi;
use App\Filament\Portal\Resources\PrestasiSayaResource;
use App\Models\Prestasi;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPrestasiSaya extends EditRecord
{
    protected static string $resource = PrestasiSayaResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Prestasi $record */
        return app(SimpanPrestasi::class)->jalankan($record, $data, auth()->user());
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

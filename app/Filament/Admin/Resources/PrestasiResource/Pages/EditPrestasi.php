<?php

namespace App\Filament\Admin\Resources\PrestasiResource\Pages;

use App\Filament\Admin\Resources\PrestasiResource;
use App\Filament\Shared\Concerns\SimpanRelasiPrestasi;
use App\Models\Prestasi;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPrestasi extends EditRecord
{
    use SimpanRelasiPrestasi;

    protected static string $resource = PrestasiResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Prestasi $record */
        return $this->simpanSemua($record, $data);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Prestasi $prestasi */
        $prestasi = $this->getRecord();

        return $this->muatRelasi($prestasi, $data);
    }
}

<?php

namespace App\Filament\Portal\Resources\PrestasiSayaResource\Pages;

use App\Filament\Portal\Resources\PrestasiSayaResource;
use App\Filament\Shared\Concerns\SimpanRelasiPrestasi;
use App\Models\Prestasi;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPrestasiSaya extends EditRecord
{
    use SimpanRelasiPrestasi;

    protected static string $resource = PrestasiSayaResource::class;

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

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

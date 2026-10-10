<?php

namespace App\Filament\Portal\Resources\PrestasiSayaResource\Pages;

use App\Filament\Portal\Resources\PrestasiSayaResource;
use App\Filament\Portal\Resources\PrestasiSayaResource\Pages\Concerns\SimpanRelasiPrestasi;
use App\Models\Prestasi;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPrestasiSaya extends ViewRecord
{
    use SimpanRelasiPrestasi;

    protected static string $resource = PrestasiSayaResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Prestasi $prestasi */
        $prestasi = $this->getRecord();

        return $this->muatRelasi($prestasi, $data);
    }

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}

<?php

namespace App\Filament\Admin\Resources\KompetisiResource\Pages;

use App\Actions\Katalog\SimpanKompetisi;
use App\Filament\Admin\Resources\KompetisiResource;
use App\Models\Kompetisi;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditKompetisi extends EditRecord
{
    protected static string $resource = KompetisiResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Kompetisi $kompetisi */
        $kompetisi = $this->getRecord();
        $data['prodi_sasaran'] = $kompetisi->prodiSasaran()->pluck('prodi.id')->all();
        $data['url_poster'] = $kompetisi->poster()->value('url');

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Kompetisi $record */
        return app(SimpanKompetisi::class)->jalankan($record, $data, auth()->user());
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

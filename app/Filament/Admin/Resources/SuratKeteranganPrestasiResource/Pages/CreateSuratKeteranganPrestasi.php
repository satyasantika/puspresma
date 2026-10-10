<?php

namespace App\Filament\Admin\Resources\SuratKeteranganPrestasiResource\Pages;

use App\Actions\Surat\TerbitkanSuratKeteranganPrestasi;
use App\Filament\Admin\Resources\SuratKeteranganPrestasiResource;
use App\Models\Mahasiswa;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSuratKeteranganPrestasi extends CreateRecord
{
    protected static string $resource = SuratKeteranganPrestasiResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        return app(TerbitkanSuratKeteranganPrestasi::class)->jalankan(
            Mahasiswa::findOrFail($data['mahasiswa_id']), array_values($data['prestasi_ids'] ?? []), $data, auth()->user(),
        );
    }
}

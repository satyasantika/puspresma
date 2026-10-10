<?php

namespace App\Filament\Portal\Resources\PrestasiSayaResource\Pages;

use App\Actions\Laporan\RekapPribadiPdf;
use App\Filament\Portal\Resources\PrestasiSayaResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrestasiSaya extends ListRecords
{
    protected static string $resource = PrestasiSayaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('unduhRekap')->label('Unduh rekap prestasi saya (PDF)')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->tooltip('Bahan SKPI/portofolio, bukan dokumen resmi')
                ->action(fn () => app(RekapPribadiPdf::class)->stream(auth()->user())),
            CreateAction::make()->label('Catat prestasi'),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\BobotPoinResource\Pages;

use App\Filament\Admin\Pages\MatriksBobotPoin;
use App\Filament\Admin\Resources\BobotPoinResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBobotPoin extends ManageRecords
{
    protected static string $resource = BobotPoinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('matriks')->label('Matriks bobot')->icon('heroicon-o-table-cells')->color('gray')
                ->url(fn (): string => MatriksBobotPoin::getUrl()),
            CreateAction::make(),
        ];
    }
}

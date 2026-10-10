<?php

namespace App\Filament\Admin\Resources\PrestasiResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RiwayatStatusRelationManager extends RelationManager
{
    protected static string $relationship = 'riwayatStatus';

    protected static ?string $title = 'Riwayat Status';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i'),
                TextColumn::make('dari_status')->label('Dari')->formatStateUsing(fn ($state) => $state?->label() ?? 'Dibuat'),
                TextColumn::make('ke_status')->label('Ke')->formatStateUsing(fn ($state) => $state?->label())->badge(),
                TextColumn::make('user.name')->label('Oleh'),
                TextColumn::make('catatan')->label('Catatan')->wrap()->placeholder('—'),
            ])
            ->paginated(false);
    }
}

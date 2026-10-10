<?php

namespace App\Filament\Admin\Resources\PrestasiResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Snapshot URL bukti saat verifikasi/penggantian (read-only, BR-28). */
class SnapshotTautanRelationManager extends RelationManager
{
    protected static string $relationship = 'snapshotTautan';

    protected static ?string $title = 'Snapshot Tautan';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('pembuat'))
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i'),
                TextColumn::make('alasan')->label('Alasan')->badge(),
                TextColumn::make('jenis')->label('Jenis'),
                TextColumn::make('url')->label('URL saat itu')->limit(60)->tooltip(fn ($state) => $state)->copyable(),
                TextColumn::make('status_cek')->label('Status cek'),
                IconColumn::make('dibuka_verifikator')->label('Dibuka')->boolean(),
                TextColumn::make('pembuat.name')->label('Oleh'),
            ])
            ->paginated(false);
    }
}

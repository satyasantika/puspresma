<?php

namespace App\Filament\Admin\Resources\PrestasiResource\RelationManagers;

use App\Enums\StatusTinjauDuplikat;
use App\Filament\Admin\Support\AksiTinjauDuplikat;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KandidatDuplikatRelationManager extends RelationManager
{
    protected static string $relationship = 'kandidatDuplikat';

    protected static ?string $title = 'Kandidat Duplikat';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('pembanding'))
            ->columns([
                TextColumn::make('pembanding.nomor_registrasi')->label('Pembanding')->placeholder('—'),
                TextColumn::make('pembanding.nama_kegiatan')->label('Kegiatan pembanding')->limit(40),
                TextColumn::make('alasan')->label('Alasan')->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('skor')->label('Skor'),
                TextColumn::make('status_tinjau')->label('Status')->badge()
                    ->formatStateUsing(fn (?StatusTinjauDuplikat $state) => $state?->label())->color(fn (?StatusTinjauDuplikat $state) => $state?->warna()),
            ])
            ->recordActions([AksiTinjauDuplikat::tinjau()])
            ->paginated(false);
    }
}

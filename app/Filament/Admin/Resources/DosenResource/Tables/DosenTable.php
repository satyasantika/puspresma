<?php

namespace App\Filament\Admin\Resources\DosenResource\Tables;

use App\Actions\Master\BuatAkunDosen;
use App\Models\Dosen;
use App\Support\ProdiScope;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DosenTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => ProdiScope::untuk(auth()->user(), $query->with('prodi')))
            ->defaultSort('nama')
            ->columns([
                TextColumn::make('nidn')->label('NIDN')->searchable()->placeholder('—'),
                TextColumn::make('nip')->label('NIP')->searchable()->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nama_lengkap')->label('Nama')->searchable(['nama'])->sortable(['nama']),
                TextColumn::make('prodi.nama')->label('Prodi')->placeholder('—'),
                TextColumn::make('email')->label('Surel')->searchable()
                    ->visible(fn (): bool => (bool) auth()->user()?->can('master.kelola')),
            ])
            ->filters([
                SelectFilter::make('prodi_id')->label('Prodi')->relationship('prodi', 'nama'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('buatAkun')->label('Buat akun')->icon('heroicon-o-user-plus')
                    ->visible(fn (Dosen $record): bool => $record->user_id === null && (bool) auth()->user()?->can('buatAkun', $record))
                    ->requiresConfirmation()
                    ->action(function (Dosen $record): void {
                        app(BuatAkunDosen::class)->jalankan($record, auth()->user());
                        Notification::make()->title('Akun dibuat')->success()->send();
                    }),
                DeleteAction::make(),
            ]);
    }
}

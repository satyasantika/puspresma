<?php

namespace App\Filament\Admin\Resources\MahasiswaResource\Tables;

use App\Actions\Master\BuatAkunMahasiswa;
use App\Enums\StatusMahasiswa;
use App\Models\Mahasiswa;
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

class MahasiswaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => ProdiScope::untuk(auth()->user(), $query->with('prodi')))
            ->defaultSort('npm')
            ->columns([
                TextColumn::make('npm')->label('NPM')->searchable()->sortable(),
                TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
                TextColumn::make('prodi.nama')->label('Prodi'),
                TextColumn::make('angkatan')->label('Angkatan')->sortable(),
                TextColumn::make('status_mahasiswa')->label('Status')->badge()
                    ->formatStateUsing(fn (?StatusMahasiswa $state) => $state?->label()),
                // BR-26: surel dan nomor HP hanya untuk pengelola master (admin-fakultas, super-admin).
                TextColumn::make('email')->label('Surel')->searchable()
                    ->visible(fn (): bool => (bool) auth()->user()?->can('master.kelola')),
            ])
            ->filters([
                SelectFilter::make('prodi_id')->label('Prodi')->relationship('prodi', 'nama'),
                SelectFilter::make('angkatan')->label('Angkatan')
                    ->options(fn () => Mahasiswa::query()->distinct()->orderByDesc('angkatan')->pluck('angkatan', 'angkatan')->all()),
                SelectFilter::make('status_mahasiswa')->label('Status')
                    ->options(collect(StatusMahasiswa::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('buatAkun')->label('Buat akun')->icon('heroicon-o-user-plus')
                    ->visible(fn (Mahasiswa $record): bool => $record->user_id === null && (bool) auth()->user()?->can('buatAkun', $record))
                    ->requiresConfirmation()
                    ->action(function (Mahasiswa $record): void {
                        app(BuatAkunMahasiswa::class)->jalankan($record, auth()->user());
                        Notification::make()->title('Akun dibuat')->success()->send();
                    }),
                DeleteAction::make(),
            ]);
    }
}

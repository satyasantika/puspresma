<?php

namespace App\Filament\Admin\Resources\UserResource\Tables;

use App\Actions\Pengguna\HapusPengguna;
use App\Actions\Pengguna\ResetMfaPengguna;
use App\Actions\Pengguna\SimpanPengguna;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('email')->label('Surel')->searchable(),
                TextColumn::make('npm')->label('NPM')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nidn')->label('NIDN')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('roles.name')->label('Peran')->badge(),
                ToggleColumn::make('is_active')->label('Aktif')
                    ->disabled(fn (User $record): bool => ! auth()->user()?->can('update', $record))
                    ->updateStateUsing(function (User $record, bool $state): bool {
                        app(SimpanPengguna::class)->ubahAktif($record, $state, auth()->user());

                        return $state;
                    }),
                TextColumn::make('last_login_at')->label('Login terakhir')->dateTime('d M Y H:i')->sortable()->placeholder('belum pernah'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Aktif'),
                SelectFilter::make('peran')->label('Peran')
                    ->options(array_combine(array_keys(PeranDanIzinSeeder::PERAN), array_keys(PeranDanIzinSeeder::PERAN)))
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['value'] ?? null)) {
                            $query->whereHas('roles', fn (Builder $q) => $q->where('name', $data['value']));
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('resetMfa')->label('Reset MFA')->icon('heroicon-o-key')->color('warning')
                    ->visible(fn (User $record): bool => (bool) auth()->user()?->can('resetMfa', $record))
                    ->requiresConfirmation()
                    ->modalDescription('Rahasia authenticator dan kode pemulihan pengguna dihapus. Pengguna harus menyiapkan MFA kembali.')
                    ->action(function (User $record): void {
                        app(ResetMfaPengguna::class)->jalankan($record, auth()->user());
                        Notification::make()->title('MFA direset')->success()->send();
                    }),
                DeleteAction::make()
                    ->using(fn (User $record) => app(HapusPengguna::class)->jalankan($record, auth()->user())),
            ]);
    }
}

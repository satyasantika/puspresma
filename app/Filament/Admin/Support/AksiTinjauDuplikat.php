<?php

namespace App\Filament\Admin\Support;

use App\Actions\Duplikat\TinjauKandidatDuplikat;
use App\Enums\StatusTinjauDuplikat;
use App\Models\KandidatDuplikat;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/** Aksi "Tinjau" kandidat duplikat yang dipakai Relation Manager dan Resource global. */
class AksiTinjauDuplikat
{
    public static function tinjau(): Action
    {
        return Action::make('tinjau')->label('Tinjau')->icon('heroicon-o-magnifying-glass')
            ->visible(fn (KandidatDuplikat $record): bool => (bool) auth()->user()?->can('tinjauDuplikat', $record))
            ->schema([
                Select::make('status')->label('Hasil tinjauan')->required()
                    ->options([StatusTinjauDuplikat::BukanDuplikat->value => 'Bukan duplikat', StatusTinjauDuplikat::Duplikat->value => 'Duplikat']),
                Textarea::make('catatan')->label('Catatan (wajib bila duplikat, min. 10 karakter)')->maxLength(2000),
            ])
            ->action(function (KandidatDuplikat $record, array $data): void {
                try {
                    app(TinjauKandidatDuplikat::class)->jalankan($record, auth()->user(), StatusTinjauDuplikat::from($data['status']), $data['catatan'] ?? null);
                } catch (ValidationException|AuthorizationException $e) {
                    Notification::make()->title('Tidak dapat diproses')->body($e->getMessage())->danger()->send();

                    return;
                }
                Notification::make()->title('Tinjauan disimpan')->success()->send();
            });
    }
}

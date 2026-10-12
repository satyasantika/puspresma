<?php

namespace App\Filament\Admin\Resources\UserResource\Actions;

use App\Actions\Pengguna\MulaiImpersonasi;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Support\Facades\URL;
use Livewire\Component;

/** Tombol darurat "Masuk sebagai" pada tabel dan halaman pengguna. Hanya tampil bila MulaiImpersonasi mengizinkan. */
class AksiMasukSebagai
{
    public static function buat(): Action
    {
        return Action::make('masukSebagai')
            ->label('Masuk sebagai')
            ->icon('heroicon-o-arrow-right-end-on-rectangle')
            ->color('danger')
            ->visible(fn (User $record): bool => auth()->user() instanceof User && MulaiImpersonasi::alasanDitolak(auth()->user(), $record) === null)
            ->requiresConfirmation()
            ->modalHeading(fn (User $record): string => "Masuk sebagai {$record->name}?")
            ->modalDescription('Hanya untuk keadaan darurat. Anda akan bekerja sebagai pengguna ini dan melihat apa yang ia lihat. Awal dan akhir sesi serta seluruh tindakan selama sesi dicatat atas nama Anda di log aktivitas.')
            ->modalSubmitActionLabel('Ya, masuk sebagai pengguna ini')
            ->action(function (User $record, Component $livewire): void {
                $livewire->redirect(URL::temporarySignedRoute(
                    'impersonasi.mulai',
                    now()->addMinute(),
                    ['pengguna' => $record->getKey(), 'oleh' => auth()->id()],
                ));
            });
    }
}

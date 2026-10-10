<?php

namespace App\Filament\Portal\Resources\PrestasiSayaResource\Actions;

use App\Actions\Prestasi\AjukanPrestasi;
use App\Actions\Prestasi\TarikKembaliPrestasi;
use App\Exceptions\PrestasiTidakLengkap;
use App\Models\Prestasi;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

/** Aksi Ajukan/Tarik Kembali yang dipakai tabel dan halaman lihat; logika di Action bisnis. */
class AksiPrestasi
{
    public static function ajukan(): Action
    {
        return Action::make('ajukan')->label('Ajukan')->icon('heroicon-o-paper-airplane')->color('primary')
            ->visible(fn (Prestasi $record): bool => (bool) auth()->user()?->can('ajukan', $record))
            ->requiresConfirmation()
            ->modalHeading('Ajukan prestasi untuk diverifikasi?')
            ->modalDescription(function (Prestasi $record): HtmlString {
                $kurang = app(AjukanPrestasi::class)->kekurangan($record);
                if ($kurang === []) {
                    return new HtmlString('Data prestasi <strong>'.e($record->nama_kegiatan).'</strong> akan dikirim ke verifikator. Setelah diajukan, data tidak dapat diubah kecuali dikembalikan.');
                }

                return new HtmlString('Prestasi belum lengkap:<ul class="list-disc ps-5">'.collect($kurang)->map(fn ($p) => '<li>'.e($p).'</li>')->implode('').'</ul>');
            })
            ->action(function (Prestasi $record): void {
                try {
                    app(AjukanPrestasi::class)->jalankan($record, auth()->user());
                } catch (PrestasiTidakLengkap $e) {
                    Notification::make()->title('Prestasi belum lengkap')->body(implode("\n", $e->pesan))->danger()->send();

                    return;
                }
                Notification::make()->title('Prestasi diajukan')->success()->send();
            });
    }

    public static function tarikKembali(): Action
    {
        return Action::make('tarikKembali')->label('Tarik kembali')->icon('heroicon-o-arrow-uturn-left')->color('gray')
            ->visible(fn (Prestasi $record): bool => (bool) auth()->user()?->can('tarikKembali', $record))
            ->requiresConfirmation()
            ->modalDescription('Pengajuan dikembalikan ke draf sehingga dapat Anda ubah kembali.')
            ->action(function (Prestasi $record): void {
                app(TarikKembaliPrestasi::class)->jalankan($record, auth()->user());
                Notification::make()->title('Pengajuan ditarik kembali')->success()->send();
            });
    }
}

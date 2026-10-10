<?php

namespace App\Filament\Shared\Concerns;

use App\Actions\Notifikasi\KirimPesanUjiWhatsApp;
use App\Actions\Notifikasi\SimpanPreferensiWhatsApp;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

/**
 * @property-read Schema $form
 */
trait HalamanProfilNotifikasi
{
    /** @var array<string, mixed> */
    public ?array $data = [];

    protected string $view = 'filament.shared.profil-notifikasi';

    public function mount(): void
    {
        $this->form->fill(['nomor_wa' => auth()->user()->nomor_wa, 'notif_wa_aktif' => auth()->user()->notif_wa_aktif]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            TextInput::make('nomor_wa')->label('Nomor WhatsApp')->tel()->maxLength(20)
                ->helperText('Boleh diawali 08…; disimpan sebagai 62…. Data pribadi: hanya Anda dan admin fakultas yang dapat melihatnya.'),
            Toggle::make('notif_wa_aktif')->label('Kirim notifikasi lewat WhatsApp')
                ->helperText('Hanya berlaku bila WhatsApp diaktifkan admin. Surel dan notifikasi di aplikasi tetap dikirim.'),
        ]);
    }

    public function simpan(): void
    {
        $this->jalankanAman(function (): void {
            $data = $this->form->getState();
            $pengguna = app(SimpanPreferensiWhatsApp::class)->jalankan(auth()->user(), $data['nomor_wa'] ?? null, (bool) ($data['notif_wa_aktif'] ?? false));
            $this->form->fill(['nomor_wa' => $pengguna->nomor_wa, 'notif_wa_aktif' => $pengguna->notif_wa_aktif]);
            Notification::make()->title('Preferensi disimpan')->success()->send();
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pesanUji')->label('Kirim pesan uji')->icon('heroicon-o-paper-airplane')->color('gray')
                ->action(fn () => $this->jalankanAman(function (): void {
                    app(KirimPesanUjiWhatsApp::class)->jalankan(auth()->user()->refresh());
                    Notification::make()->title('Pesan uji dijadwalkan')->success()->send();
                })),
        ];
    }

    private function jalankanAman(\Closure $kerja): void
    {
        try {
            $kerja();
        } catch (ValidationException $e) {
            Notification::make()->title('Tidak dapat diproses')->body(collect($e->errors())->flatten()->implode("\n"))->danger()->send();
        }
    }
}

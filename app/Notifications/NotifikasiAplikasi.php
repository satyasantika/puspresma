<?php

namespace App\Notifications;

use App\Filament\Admin\Resources\PrestasiResource;
use App\Filament\Portal\Resources\PrestasiSayaResource;
use App\Models\Prestasi;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification as NotifikasiFilament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Dasar semua notifikasi aplikasi: selalu lewat antrean `notifikasi`, setelah commit (STANDAR: tidak ada kirim sinkron).
 * Subkelas cukup mendefinisikan kanal, judul, isi, dan tautan.
 */
abstract class NotifikasiAplikasi extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 120];

    public function __construct()
    {
        $this->onQueue(config('puspresma.antrean.notifikasi'));
        $this->afterCommit();
    }

    /** @return list<string> kanal bawaan: 'database', 'mail' */
    abstract protected function kanal(): array;

    abstract protected function judul(): string;

    abstract protected function isi(User $penerima): string;

    abstract protected function url(User $penerima): ?string;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->kanal();
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */
        $surat = (new MailMessage)->subject($this->judul())
            ->greeting('Halo '.$notifiable->name.',')
            ->line($this->isi($notifiable));

        if (($url = $this->url($notifiable)) !== null) {
            $surat->action('Buka di Puspresma', $url);
        }

        return $surat->salutation('Salam, Puspresma FKIP Unsil');
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        /** @var User $notifiable */
        $pesan = NotifikasiFilament::make()->title($this->judul())->body($this->isi($notifiable));

        if (($url = $this->url($notifiable)) !== null) {
            $pesan->actions([Action::make('buka')->label('Buka')->url($url)]);
        }

        return $pesan->getDatabaseMessage();
    }

    /** Tautan ke prestasi pada panel yang sesuai peran penerima. */
    protected function urlPrestasi(User $penerima, Prestasi $prestasi): ?string
    {
        if ($penerima->canAccessPanel(Filament::getPanel('admin'))) {
            return PrestasiResource::getUrl('view', ['record' => $prestasi], panel: 'admin');
        }

        if ($penerima->canAccessPanel(Filament::getPanel('portal'))) {
            return PrestasiSayaResource::getUrl('view', ['record' => $prestasi], panel: 'portal');
        }

        return null;
    }
}

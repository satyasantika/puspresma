<?php

namespace App\Notifications\Concerns;

use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use App\Services\Pengaturan;
use App\Support\NomorWhatsApp;

/** Menambah kanal WhatsApp bila diaktifkan sistem, diaktifkan pengguna, dan nomornya valid. */
trait BisaWhatsApp
{
    /** @return list<string> */
    public function via(object $notifiable): array
    {
        $kanal = $this->kanal();

        if ($this->bolehWhatsApp($notifiable)) {
            $kanal[] = WhatsAppChannel::class;
        }

        return $kanal;
    }

    public function toWhatsApp(object $notifiable): string
    {
        /** @var User $notifiable */
        $pesan = "Puspresma FKIP Unsil\n{$this->judul()}\n{$this->isi($notifiable)}";
        if (($url = $this->url($notifiable)) !== null) {
            $pesan .= "\n{$url}";
        }

        return mb_substr($pesan, 0, 1000);
    }

    private function bolehWhatsApp(object $notifiable): bool
    {
        return (bool) Pengaturan::ambil('wa_aktif', false)
            && (bool) ($notifiable->notif_wa_aktif ?? false)
            && NomorWhatsApp::normalisasi($notifiable->nomor_wa ?? null) !== null;
    }
}

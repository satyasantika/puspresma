<?php

namespace App\Notifications\Channels;

use App\Jobs\KirimPesanWhatsApp;
use App\Support\NomorWhatsApp;
use Illuminate\Notifications\Notification;

/** Kanal WA: menjadwalkan job terpisah sehingga kegagalan gateway tidak mengulang kanal surel/database. */
class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $nomor = NomorWhatsApp::normalisasi($notifiable->nomor_wa ?? null);
        if ($nomor === null || ! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $pesan = mb_substr((string) $notification->toWhatsApp($notifiable), 0, 1000);

        KirimPesanWhatsApp::dispatch($nomor, $pesan);
    }
}

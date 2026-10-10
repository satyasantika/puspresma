<?php

namespace App\Support;

use App\Models\User;
use Spatie\Backup\Notifications\Notifiable;

/** Notifikasi backup (gagal/tidak sehat) dikirim ke surel seluruh super-admin. */
class PenerimaNotifikasiBackup extends Notifiable
{
    /** @return array<int, string> */
    public function routeNotificationForMail(): array
    {
        /** @var array<int, string> $surel */
        $surel = User::role('super-admin')->whereNotNull('email')->pluck('email')->all();

        return $surel;
    }
}

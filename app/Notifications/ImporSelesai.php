<?php

namespace App\Notifications;

use App\Enums\StatusImpor;
use App\Models\RiwayatImpor;
use Filament\Notifications\Notification as NotifikasiFilament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ImporSelesai extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public RiwayatImpor $riwayat)
    {
        $this->onQueue(config('puspresma.antrean.notifikasi'));
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $r = $this->riwayat;
        $berhasil = $r->status === StatusImpor::Selesai;

        $notifikasi = NotifikasiFilament::make()
            ->title($berhasil ? "Impor {$r->jenis} selesai" : "Impor {$r->jenis} gagal")
            ->body($berhasil
                ? "{$r->berhasil} baris berhasil, {$r->gagal} gagal dari {$r->total_baris} baris."
                : ($r->pesan ?? 'Impor tidak dapat diproses.'));

        return $berhasil && $r->gagal === 0 ? $notifikasi->success()->getDatabaseMessage() : $notifikasi->warning()->getDatabaseMessage();
    }
}

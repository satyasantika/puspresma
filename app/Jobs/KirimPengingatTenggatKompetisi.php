<?php

namespace App\Jobs;

use App\Models\KompetisiMinat;
use App\Models\User;
use App\Notifications\PengingatTenggatKompetisi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

/** Pengingat H-3 dan H-1 bagi mahasiswa berminat; kolom penanda mencegah pengiriman ganda. */
class KirimPengingatTenggatKompetisi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct()
    {
        $this->onQueue(config('puspresma.antrean.notifikasi'));
    }

    public function handle(): void
    {
        $sekarang = now();

        KompetisiMinat::query()
            ->whereHas('kompetisi', fn ($q) => $q->masihDibuka()->where('tenggat_pendaftaran', '<=', $sekarang->copy()->addDays(3)))
            ->with(['kompetisi', 'mahasiswa'])
            ->chunkById(200, function ($kelompok) use ($sekarang): void {
                foreach ($kelompok as $minat) {
                    $this->proses($minat, $sekarang);
                }
            });
    }

    private function proses(KompetisiMinat $minat, Carbon $sekarang): void
    {
        $kompetisi = $minat->kompetisi;
        $pengguna = $minat->mahasiswa->user_id === null ? null : User::where('id', $minat->mahasiswa->user_id)->where('is_active', true)->first();

        if ($pengguna === null) {
            return;
        }

        $dalamSehari = $kompetisi->tenggat_pendaftaran->lte($sekarang->copy()->addDay());

        if ($dalamSehari && $minat->diingatkan_h1_at === null) {
            Notification::send($pengguna, new PengingatTenggatKompetisi($kompetisi, 1));
            $minat->forceFill(['diingatkan_h1_at' => $sekarang, 'diingatkan_h3_at' => $minat->diingatkan_h3_at ?? $sekarang])->save();
        } elseif (! $dalamSehari && $minat->diingatkan_h3_at === null) {
            Notification::send($pengguna, new PengingatTenggatKompetisi($kompetisi, 3));
            $minat->forceFill(['diingatkan_h3_at' => $sekarang])->save();
        }
    }
}

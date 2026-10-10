<?php

namespace App\Console\Commands;

use App\Models\Dosen;
use App\Models\Prestasi;
use App\Models\User;
use App\Notifications\PengingatKonfirmasiPembimbing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class PengingatPembimbing extends Command
{
    protected $signature = 'puspresma:pengingat-pembimbing';

    protected $description = 'Ingatkan dosen pembimbing yang permintaan konfirmasinya menunggu lebih dari 7 hari';

    public function handle(): int
    {
        $terkirim = 0;

        Prestasi::query()->where('status_konfirmasi_pembimbing', 'menunggu')->where('status', '!=', 'draf')
            ->where('diajukan_at', '<', now()->subDays(7))->whereNotNull('dosen_pembimbing_id')
            ->selectRaw('dosen_pembimbing_id, COUNT(*) as jumlah')->groupBy('dosen_pembimbing_id')->toBase()->get()
            ->each(function (object $baris) use (&$terkirim): void {
                $userId = Dosen::whereKey($baris->dosen_pembimbing_id)->value('user_id');
                $pengguna = $userId === null ? null : User::where('id', $userId)->where('is_active', true)->first();
                if ($pengguna !== null) {
                    Notification::send($pengguna, new PengingatKonfirmasiPembimbing((int) $baris->jumlah));
                    $terkirim++;
                }
            });

        $this->info("{$terkirim} pengingat dikirim.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Jobs;

use App\Services\FonnteClient;
use App\Support\NomorWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Pengiriman WA terpisah dari surel: gagal di sini diulang (3×, jeda 60 dtk) tanpa menggagalkan kanal lain. */
class KirimPesanWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public string $nomor, public string $pesan)
    {
        $this->onQueue(config('puspresma.antrean.notifikasi'));
    }

    public function handle(FonnteClient $klien): void
    {
        try {
            $klien->kirim($this->nomor, $this->pesan);
        } catch (Throwable $e) {
            Log::warning('WhatsApp gagal terkirim', ['tujuan' => NomorWhatsApp::samarkan($this->nomor), 'percobaan' => $this->attempts()]);

            throw $e;
        }

        Log::info('WhatsApp terkirim', ['tujuan' => NomorWhatsApp::samarkan($this->nomor)]);
    }
}

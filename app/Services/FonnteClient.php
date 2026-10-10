<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Pembungkus gateway Fonnte agar mudah diganti; token tidak pernah dicatat di log. */
class FonnteClient
{
    public function kirim(string $nomor, string $pesan): void
    {
        $token = (string) config('services.fonnte.token');
        if ($token === '') {
            throw new RuntimeException('Token Fonnte belum dikonfigurasi.');
        }

        $respons = Http::timeout(10)->withHeaders(['Authorization' => $token])->asForm()
            ->post((string) config('services.fonnte.url'), ['target' => $nomor, 'message' => $pesan]);

        // Fonnte membalas 200 dengan {"status": false, "reason": ...} saat gagal (perlu diverifikasi di dokumentasi resmi).
        if ($respons->failed() || $respons->json('status') === false) {
            throw new RuntimeException('Pengiriman WhatsApp gagal (HTTP '.$respons->status().').');
        }
    }
}

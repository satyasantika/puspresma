<?php

namespace App\Actions\Notifikasi;

use App\Jobs\KirimPesanWhatsApp;
use App\Models\User;
use App\Services\Pengaturan;
use App\Support\NomorWhatsApp;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/** Mengirim pesan uji ke nomor WA pengguna sendiri; dibatasi 3 kali per jam. */
class KirimPesanUjiWhatsApp
{
    public function jalankan(User $pengguna): void
    {
        $nomor = NomorWhatsApp::normalisasi($pengguna->nomor_wa);
        if ($nomor === null) {
            throw ValidationException::withMessages(['nomor_wa' => 'Simpan nomor WhatsApp yang valid terlebih dahulu.']);
        }

        if (! Pengaturan::ambil('wa_aktif', false)) {
            throw ValidationException::withMessages(['wa' => 'Notifikasi WhatsApp belum diaktifkan oleh admin.']);
        }

        $kunci = 'pesan-uji-wa:'.$pengguna->getKey();
        if (RateLimiter::tooManyAttempts($kunci, 3)) {
            throw ValidationException::withMessages(['wa' => 'Pesan uji dibatasi 3 kali per jam; coba lagi nanti.']);
        }
        RateLimiter::hit($kunci, 3600);

        KirimPesanWhatsApp::dispatch($nomor, 'Puspresma FKIP Unsil: ini pesan uji notifikasi WhatsApp Anda.');
    }
}

<?php

namespace App\Actions\Notifikasi;

use App\Models\User;
use App\Support\NomorWhatsApp;
use Illuminate\Validation\ValidationException;

/** Menyimpan nomor WA (dinormalisasi 08xx → 628xx) dan sakelar notifikasi WA milik pengguna sendiri. */
class SimpanPreferensiWhatsApp
{
    public function jalankan(User $pengguna, ?string $nomor, bool $aktif): User
    {
        $normal = filled($nomor) ? NomorWhatsApp::normalisasi($nomor) : null;

        if (filled($nomor) && $normal === null) {
            throw ValidationException::withMessages(['nomor_wa' => 'Nomor WhatsApp tidak valid. Contoh: 081234567890.']);
        }

        if ($aktif && $normal === null) {
            throw ValidationException::withMessages(['notif_wa_aktif' => 'Isi nomor WhatsApp yang valid sebelum mengaktifkan notifikasi WhatsApp.']);
        }

        $pengguna->forceFill(['nomor_wa' => $normal, 'notif_wa_aktif' => $aktif])->save();

        return $pengguna;
    }
}

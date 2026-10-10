<?php

namespace App\Actions\Galeri;

use App\Models\Prestasi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/** Moderasi galeri: sembunyikan dengan alasan tanpa mengubah status verifikasi (US-GAL-03). */
class SembunyikanDariGaleri
{
    public function jalankan(Prestasi $prestasi, User $pelaku, string $alasan): Prestasi
    {
        if (! $pelaku->can('galeri.moderasi')) {
            throw new AuthorizationException('Anda tidak berhak memoderasi galeri.');
        }

        if (mb_strlen(trim($alasan)) < 10) {
            throw ValidationException::withMessages(['alasan' => 'Alasan wajib diisi minimal 10 karakter.']);
        }

        $prestasi->update(['disembunyikan_dari_galeri' => true, 'alasan_disembunyikan' => mb_substr(trim($alasan), 0, 255)]);
        activity('galeri')->performedOn($prestasi)->causedBy($pelaku)->event('disembunyikan')->withProperties(['alasan' => trim($alasan)])->log('Prestasi disembunyikan dari galeri');
        Cache::increment('galeri:versi');

        return $prestasi;
    }
}

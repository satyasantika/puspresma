<?php

namespace App\Actions\Galeri;

use App\Models\Prestasi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;

class TampilkanKembaliDiGaleri
{
    public function jalankan(Prestasi $prestasi, User $pelaku, ?string $alasan = null): Prestasi
    {
        if (! $pelaku->can('galeri.moderasi')) {
            throw new AuthorizationException('Anda tidak berhak memoderasi galeri.');
        }

        $prestasi->update(['disembunyikan_dari_galeri' => false, 'alasan_disembunyikan' => null]);
        activity('galeri')->performedOn($prestasi)->causedBy($pelaku)->event('ditampilkan')->withProperties(['alasan' => $alasan])->log('Prestasi ditampilkan kembali di galeri');
        Cache::increment('galeri:versi');

        return $prestasi;
    }
}

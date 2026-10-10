<?php

namespace App\Actions\Galeri;

use App\Models\PrestasiAnggota;
use App\Models\User;
use App\Services\Pengaturan;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;

/**
 * Persetujuan publikasi opt-in per anggota (BR-17, UU PDP): hanya pemilik baris yang dapat mengubahnya,
 * tercatat lengkap (waktu, IP, user agent, versi teks) dan cache galeri dibuang seketika.
 */
class UbahPersetujuanPublikasi
{
    public function jalankan(PrestasiAnggota $anggota, User $pelaku, bool $setuju): PrestasiAnggota
    {
        $pelaku->loadMissing('mahasiswa');
        if ($pelaku->mahasiswa === null || $pelaku->mahasiswa->id !== $anggota->mahasiswa_id) {
            throw new AuthorizationException('Persetujuan hanya dapat diubah oleh mahasiswa yang bersangkutan.');
        }

        if ($anggota->setuju_publikasi === $setuju) {
            return $anggota;
        }

        $anggota->update(['setuju_publikasi' => $setuju, 'setuju_publikasi_at' => $setuju ? now() : null]);

        activity('persetujuan')->performedOn($anggota)->causedBy($pelaku)
            ->event($setuju ? 'disetujui' : 'ditarik')
            ->withProperties([
                'prestasi_id' => $anggota->prestasi_id,
                'nilai' => $setuju,
                'ip' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
                'versi_teks' => md5((string) Pengaturan::ambil('teks_persetujuan_publikasi', '')),
            ])
            ->log($setuju ? 'Persetujuan publikasi diberikan' : 'Persetujuan publikasi ditarik');

        Cache::increment('galeri:versi');

        return $anggota;
    }
}

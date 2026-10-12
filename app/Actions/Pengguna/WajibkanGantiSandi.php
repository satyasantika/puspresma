<?php

namespace App\Actions\Pengguna;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

/**
 * Super-admin menandai akun agar wajib mengganti kata sandi pada akses berikutnya (atau mencabut tanda itu).
 * Menandai tidak mengeluarkan sesi aktif; pengguna diarahkan ke halaman profil pada permintaan berikutnya.
 */
class WajibkanGantiSandi
{
    /**
     * @param  Collection<int, User>  $pengguna
     * @return int jumlah akun yang berubah
     */
    public function jalankan(Collection $pengguna, bool $wajib, User $pelaku): int
    {
        if (! $pelaku->can('pengguna.kelola')) {
            throw new AuthorizationException('Anda tidak berhak mengelola pengguna.');
        }

        $berubah = 0;

        foreach ($pengguna as $user) {
            if ($user->wajib_ganti_sandi === $wajib) {
                continue;
            }

            $user->forceFill(['wajib_ganti_sandi' => $wajib])->save();
            $berubah++;

            activity('pengguna')
                ->event($wajib ? 'wajib_ganti_sandi' : 'wajib_ganti_sandi_dicabut')
                ->causedBy($pelaku)->performedOn($user)
                ->log($wajib ? 'Akun diwajibkan mengganti kata sandi' : 'Kewajiban ganti kata sandi dicabut');
        }

        return $berubah;
    }
}

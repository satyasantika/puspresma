<?php

namespace App\Services;

use App\Models\Prestasi;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/** Menentukan penerima notifikasi prestasi; anggota tanpa akun dilewati dan dicatat jumlahnya. */
class PenerimaNotifikasi
{
    /**
     * Admin yang memantau antrean: admin-prodi pengaju bila tahap 1 aktif, selain itu semua admin-fakultas.
     *
     * @return Collection<int, User>
     */
    public function antreanVerifikasi(Prestasi $prestasi): Collection
    {
        if (Pengaturan::ambil('verifikasi_prodi_aktif', false)) {
            return User::role('admin-prodi')->where('is_active', true)->where('prodi_id', $prestasi->prodi_id)->get();
        }

        return $this->adminFakultas();
    }

    /** @return Collection<int, User> */
    public function adminFakultas(): Collection
    {
        return User::role('admin-fakultas')->where('is_active', true)->get();
    }

    /**
     * Anggota internal yang punya akun aktif.
     *
     * @return Collection<int, User>
     */
    public function anggotaInternal(Prestasi $prestasi): Collection
    {
        $mahasiswa = $prestasi->mahasiswa()->get();
        $punyaAkun = $mahasiswa->whereNotNull('user_id');

        if ($mahasiswa->count() > $punyaAkun->count()) {
            Log::info('Notifikasi: anggota tanpa akun dilewati', ['prestasi_id' => $prestasi->id, 'dilewati' => $mahasiswa->count() - $punyaAkun->count()]);
        }

        return User::whereIn('id', $punyaAkun->pluck('user_id'))->where('is_active', true)->get();
    }

    public function pengaju(Prestasi $prestasi): ?User
    {
        $pengaju = $prestasi->pengaju()->first();

        return $pengaju?->user_id === null ? null : User::where('id', $pengaju->user_id)->where('is_active', true)->first();
    }

    public function pembimbing(Prestasi $prestasi): ?User
    {
        $dosen = $prestasi->dosenPembimbing()->first();

        return $dosen?->user_id === null ? null : User::where('id', $dosen->user_id)->where('is_active', true)->first();
    }
}

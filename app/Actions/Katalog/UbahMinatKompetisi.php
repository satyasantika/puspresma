<?php

namespace App\Actions\Katalog;

use App\Models\Kompetisi;
use App\Models\KompetisiMinat;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/** Mahasiswa menandai/membatalkan minat pada kompetisi terbit yang masih dibuka (unik per mahasiswa). */
class UbahMinatKompetisi
{
    public function jalankan(Kompetisi $kompetisi, User $pelaku, bool $berminat): void
    {
        $pelaku->loadMissing('mahasiswa');
        if ($pelaku->mahasiswa === null || ! $pelaku->can('prestasi.buat-milik')) {
            throw new AuthorizationException('Hanya mahasiswa yang dapat menandai minat.');
        }

        if (! $berminat) {
            KompetisiMinat::where('kompetisi_id', $kompetisi->id)->where('mahasiswa_id', $pelaku->mahasiswa->id)->delete();

            return;
        }

        if (! Kompetisi::masihDibuka()->whereKey($kompetisi->getKey())->exists()) {
            throw ValidationException::withMessages(['kompetisi' => 'Pendaftaran kompetisi ini sudah ditutup.']);
        }

        KompetisiMinat::firstOrCreate(['kompetisi_id' => $kompetisi->id, 'mahasiswa_id' => $pelaku->mahasiswa->id]);
    }
}

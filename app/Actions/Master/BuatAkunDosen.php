<?php

namespace App\Actions\Master;

use App\Models\Dosen;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Membuat akun pengguna (peran dosen) dari data master dosen. */
class BuatAkunDosen
{
    public function jalankan(Dosen $dosen, User $pelaku): User
    {
        if (! $pelaku->can('buatAkun', $dosen)) {
            throw new AuthorizationException('Anda tidak berhak membuat akun dosen.');
        }

        if ($dosen->user_id !== null) {
            throw ValidationException::withMessages(['user_id' => 'Dosen ini sudah memiliki akun.']);
        }

        if (blank($dosen->email)) {
            throw ValidationException::withMessages(['email' => 'Isi surel dosen terlebih dahulu.']);
        }

        if (User::where('email', $dosen->email)->exists()) {
            throw ValidationException::withMessages(['email' => 'Surel sudah dipakai akun lain.']);
        }

        return DB::transaction(function () use ($dosen): User {
            $user = User::create([
                'name' => $dosen->nama_lengkap,
                'email' => $dosen->email,
                'password' => Str::random(40),
                'nidn' => $dosen->nidn,
                'nip' => $dosen->nip,
                'prodi_id' => $dosen->prodi_id,
            ]);
            $user->assignRole('dosen');
            $dosen->update(['user_id' => $user->id]);

            return $user;
        });
    }
}

<?php

namespace App\Actions\Master;

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Membuat akun pengguna (peran mahasiswa) dari data master; kata sandi acak, masuk lewat Google atau tautan atur sandi. */
class BuatAkunMahasiswa
{
    public function jalankan(Mahasiswa $mahasiswa, User $pelaku): User
    {
        if (! $pelaku->can('buatAkun', $mahasiswa)) {
            throw new AuthorizationException('Anda tidak berhak membuat akun mahasiswa.');
        }

        if ($mahasiswa->user_id !== null) {
            throw ValidationException::withMessages(['user_id' => 'Mahasiswa ini sudah memiliki akun.']);
        }

        if (blank($mahasiswa->email)) {
            throw ValidationException::withMessages(['email' => 'Isi surel mahasiswa terlebih dahulu.']);
        }

        if (User::where('email', $mahasiswa->email)->exists()) {
            throw ValidationException::withMessages(['email' => 'Surel sudah dipakai akun lain.']);
        }

        return DB::transaction(function () use ($mahasiswa): User {
            $user = User::create([
                'name' => $mahasiswa->nama,
                'email' => $mahasiswa->email,
                'password' => Str::random(40),
                'npm' => $mahasiswa->npm,
                'prodi_id' => $mahasiswa->prodi_id,
            ]);
            $user->assignRole('mahasiswa');
            $mahasiswa->update(['user_id' => $user->id]);

            return $user;
        });
    }
}

<?php

namespace App\Actions\Auth;

use App\Exceptions\MasukGoogleDitolak;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\AbstractUser as PenggunaGoogle;

/**
 * BR-20: login Google hanya untuk domain kampus dan hanya bila orangnya sudah ada di master;
 * tidak ada registrasi mandiri. Mengembalikan User yang siap di-login.
 */
class MasukDenganGoogle
{
    public function jalankan(PenggunaGoogle $google): User
    {
        $email = strtolower((string) $google->getEmail());
        $terverifikasi = filter_var($google->getRaw()['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($email === '' || ! $terverifikasi) {
            throw new MasukGoogleDitolak('Surel akun Google Anda belum terverifikasi.');
        }

        $domain = Str::after($email, '@');

        $user = match ($domain) {
            config('puspresma.domain_mahasiswa') => $this->mahasiswa($email, $google),
            config('puspresma.domain_pegawai') => $this->pegawai($email, $google),
            default => throw new MasukGoogleDitolak('Gunakan akun Google kampus (@'.config('puspresma.domain_mahasiswa').' atau @'.config('puspresma.domain_pegawai').').'),
        };

        if (! $user->is_active) {
            throw new MasukGoogleDitolak('Akun Anda dinonaktifkan');
        }

        if ($user->wajibMfa()) {
            throw new MasukGoogleDitolak('Akun ini wajib MFA. Masuk dengan surel, kata sandi, dan kode authenticator.');
        }

        if ($user->google_id !== null && $user->google_id !== $google->getId()) {
            throw new MasukGoogleDitolak('Akun ini sudah ditautkan ke akun Google lain.');
        }

        if ($user->google_id === null) {
            $user->forceFill(['google_id' => $google->getId()])->save();
        }

        return $user;
    }

    private function mahasiswa(string $email, PenggunaGoogle $google): User
    {
        $mahasiswa = Mahasiswa::where('email', $email)->first() ?? $this->mahasiswaDariNpm($email);

        if ($mahasiswa === null) {
            throw new MasukGoogleDitolak('Data Anda belum terdaftar, hubungi admin prodi');
        }

        if (! $mahasiswa->status_mahasiswa->bolehMasukPortal()) {
            throw new MasukGoogleDitolak('Status mahasiswa Anda ('.$mahasiswa->status_mahasiswa->label().') tidak dapat masuk portal.');
        }

        return DB::transaction(function () use ($mahasiswa, $email, $google): User {
            $user = $mahasiswa->user ?? User::firstWhere('email', $email) ?? new User(['email' => $email, 'password' => Str::random(40)]);
            $user->fill(['name' => $user->exists ? $user->name : ($mahasiswa->nama ?: (string) $google->getName()), 'npm' => $mahasiswa->npm, 'prodi_id' => $mahasiswa->prodi_id])->save();

            if (! $user->hasRole('mahasiswa')) {
                $user->assignRole('mahasiswa');
            }
            if ($mahasiswa->user_id !== $user->getKey()) {
                $mahasiswa->update(['user_id' => $user->getKey()]);
            }

            return $user;
        });
    }

    private function mahasiswaDariNpm(string $email): ?Mahasiswa
    {
        $lokal = Str::before($email, '@');

        return preg_match((string) config('puspresma.google_npm_pola'), $lokal, $cocok) === 1
            ? Mahasiswa::firstWhere('npm', $cocok[1])
            : null;
    }

    private function pegawai(string $email, PenggunaGoogle $google): User
    {
        $user = User::firstWhere('email', $email);
        $dosen = Dosen::where('email', $email)->first();

        if ($user === null && $dosen === null) {
            throw new MasukGoogleDitolak('Akun pegawai Anda belum terdaftar, hubungi admin fakultas');
        }

        return $this->siapkanPegawai($user, $dosen, $email, (string) $google->getName());
    }

    private function siapkanPegawai(?User $user, ?Dosen $dosen, string $email, string $namaGoogle): User
    {
        return DB::transaction(function () use ($user, $dosen, $email, $namaGoogle): User {
            $user ??= User::create([
                'name' => $dosen !== null ? $dosen->nama_lengkap : $namaGoogle,
                'email' => $email,
                'password' => Str::random(40),
                'nidn' => $dosen?->nidn,
                'nip' => $dosen?->nip,
                'prodi_id' => $dosen?->prodi_id,
            ]);

            if ($dosen !== null) {
                if (! $user->hasRole('dosen')) {
                    $user->assignRole('dosen');
                }
                if ($dosen->user_id !== $user->getKey()) {
                    $dosen->update(['user_id' => $user->getKey()]);
                }
            }

            return $user;
        });
    }
}

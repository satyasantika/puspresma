<?php

namespace App\Actions\Pengguna;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

/**
 * Pengguna mengganti kata sandinya sendiri: memeriksa sandi lama, menghapus kewajiban ganti sandi,
 * dan mengeluarkan semua sesi/perangkat lain (sesi ini tetap masuk). Kata sandi tidak pernah dicatat di log.
 */
class GantiSandi
{
    public function jalankan(User $user, #[SensitiveParameter] string $sandiLama, #[SensitiveParameter] string $sandiBaru): void
    {
        if (! Hash::check($sandiLama, $user->getAuthPassword())) {
            throw ValidationException::withMessages(['sandi_lama' => 'Kata sandi saat ini tidak sesuai.']);
        }

        Validator::make(['sandi_baru' => $sandiBaru], [
            'sandi_baru' => ['required', 'string', Password::min(12)->letters()->numbers()],
        ], [], ['sandi_baru' => 'kata sandi baru'])
            ->after(function ($validator) use ($sandiLama, $sandiBaru): void {
                if (hash_equals($sandiLama, $sandiBaru)) {
                    $validator->errors()->add('sandi_baru', 'Kata sandi baru harus berbeda dari yang lama.');
                }
            })->validate();

        $user->forceFill([
            'password' => $sandiBaru,
            'wajib_ganti_sandi' => false,
            'sandi_diubah_at' => now(),
        ])->save();

        // Mengganti hash sandi membuat AuthenticateSession menolak sesi lain; tambahan ini juga memutar token "ingat saya".
        if (Auth::check() && Auth::id() === $user->getAuthIdentifier()) {
            Auth::logoutOtherDevices($sandiBaru);
        }

        activity('pengguna')
            ->event('sandi_diganti')
            ->causedBy($user)
            ->performedOn($user)
            ->log('Kata sandi diganti; sesi lain dikeluarkan');
    }
}

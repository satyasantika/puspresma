<?php

namespace App\Actions\Pengguna;

use App\Models\User;
use Illuminate\Support\Facades\Validator;

/**
 * Pengguna mengubah profilnya sendiri. Surel dan identitas (NPM/NIDN/NIP) tidak dapat diubah di sini:
 * keduanya tertaut ke master kampus dan hanya dikelola admin.
 */
class UbahProfil
{
    /**
     * @param  array<string, mixed>  $data  name, nomor_wa
     */
    public function jalankan(User $user, array $data): User
    {
        $valid = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'nomor_wa' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
        ], ['nomor_wa.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, dan tanda + - ( ).'])->validate();

        $user->update([
            'name' => trim($valid['name']),
            'nomor_wa' => filled($valid['nomor_wa'] ?? null) ? trim($valid['nomor_wa']) : null,
        ]);

        return $user;
    }
}

<?php

namespace App\Actions\Pengguna;

use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as AturanSandi;
use Illuminate\Validation\ValidationException;

/**
 * Membuat/mengubah akun lokal beserta peran. Seluruh aturan ditegakkan di sini (bukan hanya di formulir).
 */
class SimpanPengguna
{
    /**
     * @param  array<string, mixed>  $data  name, email, password (opsional), npm, nidn, nip, nomor_wa, is_active, peran (list)
     */
    public function jalankan(?User $user, array $data, User $pelaku): User
    {
        if (! $pelaku->can('pengguna.kelola')) {
            throw new AuthorizationException('Anda tidak berhak mengelola pengguna.');
        }

        $data = $this->validasi($user, $data);
        $peran = array_values(array_unique($data['peran'] ?? []));
        $aktif = (bool) ($data['is_active'] ?? true);

        $this->pastikanAmanTerhadapSuperAdminTerakhir($user, $peran, $aktif);

        if ($user?->is($pelaku) && ! $aktif) {
            throw ValidationException::withMessages(['is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        return DB::transaction(function () use ($user, $data, $peran, $aktif) {
            $profil = collect($data)->only(['name', 'email', 'npm', 'nidn', 'nip', 'nomor_wa'])
                ->map(fn ($nilai) => filled($nilai) ? $nilai : null)
                ->all();
            $profil['is_active'] = $aktif;
            $baru = $user === null;
            $perluTautanReset = false;

            if ($baru) {
                $perluTautanReset = blank($data['password'] ?? null);
                // Tanpa kata sandi: acak, tak diketahui siapa pun; pengguna menyetelnya lewat tautan surel.
                $user = User::create($profil + ['password' => $perluTautanReset ? Str::random(40) : $data['password']]);
            } else {
                $user->update($profil + (filled($data['password'] ?? null) ? ['password' => $data['password']] : []));
            }

            $user->syncRoles($peran);

            if ($perluTautanReset) {
                $this->kirimTautanReset($user);
            }

            return $user;
        });
    }

    /** Mengaktifkan/menonaktifkan akun (dipakai toggle pada tabel). */
    public function ubahAktif(User $user, bool $aktif, User $pelaku): User
    {
        if (! $pelaku->can('pengguna.kelola')) {
            throw new AuthorizationException('Anda tidak berhak mengelola pengguna.');
        }

        if ($user->is($pelaku) && ! $aktif) {
            throw ValidationException::withMessages(['is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        $this->pastikanAmanTerhadapSuperAdminTerakhir($user, $user->roles->pluck('name')->all(), $aktif);
        $user->update(['is_active' => $aktif]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function validasi(?User $user, array $data): array
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => ['nullable', AturanSandi::min(12)->letters()->numbers()],
            'npm' => ['nullable', 'string', 'max:20'],
            'nidn' => ['nullable', 'string', 'max:20'],
            'nip' => ['nullable', 'string', 'max:30'],
            'nomor_wa' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'peran' => ['array'],
            'peran.*' => ['string', Rule::in(array_keys(PeranDanIzinSeeder::PERAN))],
        ])->validate();
    }

    /**
     * Peran super-admin terakhir tidak boleh dicabut dan akunnya tidak boleh dinonaktifkan.
     *
     * @param  list<string>  $peranBaru
     */
    protected function pastikanAmanTerhadapSuperAdminTerakhir(?User $user, array $peranBaru, bool $aktifBaru): void
    {
        if ($user === null || ! $user->hasRole('super-admin')) {
            return;
        }

        $tetapSuperAdminAktif = in_array('super-admin', $peranBaru, true) && $aktifBaru;

        if ($tetapSuperAdminAktif) {
            return;
        }

        $lain = User::role('super-admin')->where('is_active', true)->whereKeyNot($user->getKey())->count();

        if ($lain === 0) {
            throw ValidationException::withMessages([
                'peran' => 'Peran super-admin terakhir tidak dapat dicabut atau dinonaktifkan.',
            ]);
        }
    }

    private function kirimTautanReset(User $user): void
    {
        $panel = Filament::getPanel($user->canAccessPanel(Filament::getPanel('portal')) ? 'portal' : 'admin');
        $token = Password::createToken($user);

        $notifikasi = new ResetPassword($token);
        $notifikasi->url = $panel->getResetPasswordUrl($token, $user);
        $notifikasi->onQueue('notifikasi')->afterCommit();

        $user->notify($notifikasi);
    }
}

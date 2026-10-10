<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Membuat/menautkan akun super-admin (bawaan fkip@unsil.ac.id; ubah lewat SEED_SUPERADMIN_EMAIL/_NAME/_PASSWORD).
     * Kata sandi hanya diatur saat akun baru dibuat, tidak pernah menimpa kata sandi yang sudah diganti.
     * SEED_SUPERADMIN_PASSWORD kosong = sandi acak yang dicetak sekali ke konsol.
     */
    public function run(): void
    {
        $email = config('puspresma.seed_superadmin.email');

        if (blank($email)) {
            $this->command->warn('SEED_SUPERADMIN_EMAIL kosong; akun super-admin tidak dibuat.');

            return;
        }

        $user = User::firstWhere('email', $email);

        if ($user === null) {
            $sandi = config('puspresma.seed_superadmin.password') ?: Str::password(20, symbols: false);
            $acak = blank(config('puspresma.seed_superadmin.password'));
            $user = User::create([
                'name' => config('puspresma.seed_superadmin.name') ?: 'Super Admin',
                'email' => $email,
                'password' => $sandi,
                'is_active' => true,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $this->command->info("Akun super-admin dibuat: {$email}");
            if ($acak) {
                $this->command->warn("Kata sandi (ditampilkan sekali): {$sandi}");
            }
        }

        $user->assignRole('super-admin');
    }
}

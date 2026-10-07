<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Membuat/menautkan akun super-admin dari SEED_SUPERADMIN_EMAIL dan SEED_SUPERADMIN_NAME.
     * Kata sandi acak dicetak sekali ke konsol hanya saat akun baru dibuat.
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
            $sandi = Str::password(20, symbols: false);
            $user = User::create([
                'name' => config('puspresma.seed_superadmin.name') ?: 'Super Admin',
                'email' => $email,
                'password' => $sandi,
                'is_active' => true,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $this->command->info("Akun super-admin dibuat: {$email}");
            $this->command->warn("Kata sandi (ditampilkan sekali): {$sandi}");
        }

        $user->assignRole('super-admin');
    }
}

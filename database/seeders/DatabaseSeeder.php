<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Tanpa kata sandi tetap: super-admin dibuat dari env SEED_SUPERADMIN_* dengan kata sandi acak.
     */
    public function run(): void
    {
        $this->call([
            PeranDanIzinSeeder::class,
            SuperAdminSeeder::class,
            ProdiSeeder::class,
            JenisKegiatanSeeder::class,
            KategoriLombaSeeder::class,
            TingkatSeeder::class,
            CapaianSeeder::class,
        ]);
    }
}

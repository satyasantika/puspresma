<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Profil Ringan (STANDAR-TEKNIS §1b): Horizon dicabut, jadi izin `horizon.akses` tidak lagi dipakai.
 * Relasi peran dan pengguna ikut terhapus (cascade pada tabel pivot).
 */
return new class extends Migration
{
    private const NAMA = 'horizon.akses';

    public function up(): void
    {
        DB::table('permissions')->where('name', self::NAMA)->delete();

        $this->lupakanCache();
    }

    public function down(): void
    {
        // Izin dikembalikan tanpa relasi peran; pemberian ulang ke peran dilakukan oleh seeder lama.
        if (! DB::table('permissions')->where('name', self::NAMA)->exists()) {
            DB::table('permissions')->insert([
                'id' => (string) Str::uuid7(),
                'name' => self::NAMA,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->lupakanCache();
    }

    /**
     * Cache izin dibersihkan sebisanya: migrasi dapat berjalan dari image baru saat `.env` server masih
     * menunjuk Redis yang sudah tidak terjangkau, dan itu tidak boleh menggagalkan perubahan data.
     */
    private function lupakanCache(): void
    {
        rescue(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions(), report: false);
    }
};

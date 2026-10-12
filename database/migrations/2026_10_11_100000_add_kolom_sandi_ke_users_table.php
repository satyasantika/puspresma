<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Diatur super-admin: pengguna dipaksa mengganti kata sandi pada masuk berikutnya.
            $table->boolean('wajib_ganti_sandi')->default(false)->after('is_active');
            $table->timestamp('sandi_diubah_at')->nullable()->after('wajib_ganti_sandi');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wajib_ganti_sandi', 'sandi_diubah_at']);
        });
    }
};

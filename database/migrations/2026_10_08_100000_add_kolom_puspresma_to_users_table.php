<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->string('google_id', 64)->nullable()->unique()->after('email');
            $table->string('npm', 20)->nullable()->index()->after('google_id');
            $table->string('nidn', 20)->nullable()->index()->after('npm');
            $table->string('nip', 30)->nullable()->index()->after('nidn');
            $table->string('nomor_wa', 20)->nullable()->after('nip');
            $table->boolean('notif_wa_aktif')->default(false)->after('nomor_wa');
            $table->boolean('is_active')->default(true)->index()->after('notif_wa_aktif');
            $table->dateTime('last_login_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropIndex(['npm']);
            $table->dropIndex(['nidn']);
            $table->dropIndex(['nip']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['google_id', 'npm', 'nidn', 'nip', 'nomor_wa', 'notif_wa_aktif', 'is_active', 'last_login_at']);
        });
    }
};

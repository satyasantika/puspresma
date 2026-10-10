<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_status_prestasi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('prestasi_id')->constrained('prestasi')->cascadeOnDelete();
            $table->string('dari_status', 20)->nullable();
            $table->string('ke_status', 20);
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['prestasi_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_status_prestasi');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tidak dapat diubah dan tanpa updated_at (BR-28).
        Schema::create('snapshot_tautan_verifikasi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('prestasi_id')->constrained('prestasi')->cascadeOnDelete();
            $table->foreignUuid('tautan_berkas_id')->nullable()->constrained('tautan_berkas')->nullOnDelete();
            $table->string('jenis', 30);
            $table->string('url', 2048);
            $table->string('drive_file_id', 100)->nullable();
            $table->string('status_cek', 30);
            $table->string('alasan', 20)->default('verifikasi');
            $table->boolean('dibuka_verifikator')->default(false);
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['prestasi_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snapshot_tautan_verifikasi');
    }
};

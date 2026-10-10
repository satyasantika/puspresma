<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_impor', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('jenis', 20);
            $table->string('nama_berkas');
            // Berkas sumber hanya ada selama antre/proses; dikosongkan setelah diproses.
            $table->string('path_tmp')->nullable();
            $table->string('status', 20)->default('antre');
            $table->unsignedInteger('total_baris')->default(0);
            $table->unsignedInteger('berhasil')->default(0);
            $table->unsignedInteger('gagal')->default(0);
            $table->string('path_galat')->nullable();
            $table->dateTime('kedaluwarsa_at')->nullable();
            $table->text('pesan')->nullable();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['jenis', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_impor');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestasi_anggota', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('prestasi_id')->constrained('prestasi')->cascadeOnDelete();
            $table->foreignUuid('mahasiswa_id')->constrained('mahasiswa')->restrictOnDelete();
            // Snapshot prodi anggota saat ditambahkan (statistik BR-02).
            $table->foreignUuid('prodi_id')->constrained('prodi')->restrictOnDelete();
            $table->string('peran', 10)->default('anggota');
            $table->boolean('is_pengaju')->default(false);
            $table->boolean('setuju_publikasi')->default(false);
            $table->dateTime('setuju_publikasi_at')->nullable();
            $table->decimal('poin', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['prestasi_id', 'mahasiswa_id']);
        });

        Schema::create('prestasi_anggota_eksternal', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('prestasi_id')->constrained('prestasi')->cascadeOnDelete();
            $table->string('nama', 150);
            $table->string('nomor_induk', 30)->nullable();
            $table->string('institusi', 150);
            $table->string('program_studi', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestasi_anggota_eksternal');
        Schema::dropIfExists('prestasi_anggota');
    }
};

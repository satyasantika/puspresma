<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kompetisi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 191)->unique();
            $table->string('judul', 255);
            $table->string('penyelenggara', 255);
            $table->foreignUuid('jenis_kegiatan_id')->constrained('jenis_kegiatan')->restrictOnDelete();
            $table->string('bidang', 20)->nullable();
            $table->foreignUuid('kategori_lomba_id')->nullable()->constrained('kategori_lomba')->nullOnDelete();
            $table->foreignUuid('tingkat_id')->nullable()->constrained('tingkat')->nullOnDelete();
            $table->mediumText('deskripsi')->nullable();
            $table->string('url_pendaftaran', 500)->nullable();
            $table->string('url_panduan', 500)->nullable();
            $table->date('tanggal_buka')->nullable();
            $table->dateTime('tenggat_pendaftaran');
            $table->date('tanggal_pelaksanaan_mulai')->nullable();
            $table->date('tanggal_pelaksanaan_selesai')->nullable();
            $table->string('moda', 10)->nullable();
            $table->string('lokasi', 150)->nullable();
            $table->decimal('biaya_pendaftaran', 15, 2)->nullable();
            $table->string('kontak_narahubung', 150)->nullable();
            $table->string('status', 20)->default('draf');
            $table->dateTime('diterbitkan_at')->nullable();
            $table->foreignUuid('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'tenggat_pendaftaran']);
            $table->fullText(['judul', 'penyelenggara', 'deskripsi']);
        });

        Schema::create('kompetisi_prodi', function (Blueprint $table) {
            $table->foreignUuid('kompetisi_id')->constrained('kompetisi')->cascadeOnDelete();
            $table->foreignUuid('prodi_id')->constrained('prodi')->cascadeOnDelete();
            $table->primary(['kompetisi_id', 'prodi_id']);
        });

        Schema::create('kompetisi_minat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kompetisi_id')->constrained('kompetisi')->cascadeOnDelete();
            $table->foreignUuid('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->dateTime('diingatkan_h3_at')->nullable();
            $table->dateTime('diingatkan_h1_at')->nullable();
            $table->timestamps();

            $table->unique(['kompetisi_id', 'mahasiswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kompetisi_minat');
        Schema::dropIfExists('kompetisi_prodi');
        Schema::dropIfExists('kompetisi');
    }
};

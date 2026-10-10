<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hanya data; PDF dan QR dibangkitkan saat diminta dan tidak disimpan (STANDAR-TEKNIS §1a).
        Schema::create('surat_keterangan_prestasi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nomor', 100)->unique();
            $table->foreignUuid('mahasiswa_id')->constrained('mahasiswa')->restrictOnDelete();
            $table->string('keperluan', 100);
            $table->date('periode_mulai')->nullable();
            $table->date('periode_selesai')->nullable();
            $table->date('tanggal_terbit');
            $table->string('penandatangan_nama', 150);
            $table->string('penandatangan_jabatan', 150);
            $table->string('penandatangan_nip', 30)->nullable();
            $table->string('status', 10)->default('aktif');
            $table->dateTime('dicabut_at')->nullable();
            $table->string('alasan_dicabut', 255)->nullable();
            $table->foreignUuid('diterbitkan_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('surat_keterangan_prestasi_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('surat_keterangan_prestasi_id')->constrained('surat_keterangan_prestasi', indexName: 'surat_item_surat_fk')->cascadeOnDelete();
            $table->foreignUuid('prestasi_id')->constrained('prestasi', indexName: 'surat_item_prestasi_fk')->restrictOnDelete();

            $table->unique(['surat_keterangan_prestasi_id', 'prestasi_id'], 'surat_item_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_keterangan_prestasi_item');
        Schema::dropIfExists('surat_keterangan_prestasi');
    }
};

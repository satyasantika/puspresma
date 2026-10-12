<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tautan_berkas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('pemilik_type', 191);
            $table->uuid('pemilik_id');
            $table->string('jenis', 30);
            $table->string('label')->nullable();
            $table->string('url', 2048);
            $table->enum('penyedia', ['google_drive', 'google_docs', 'onedrive', 'unsil', 'lainnya']);
            $table->string('drive_file_id', 100)->nullable()->index();
            $table->enum('status_cek', ['belum', 'dapat_diakses', 'tidak_dapat_diakses'])->default('belum')->index();
            $table->dateTime('dicek_pada')->nullable();
            $table->foreignUuid('ditambahkan_oleh')->constrained('users')->restrictOnDelete();
            // Perluasan khusus Puspresma.
            $table->boolean('tampil_publik')->default(false);
            $table->string('url_normal', 2048);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pemilik_type', 'pemilik_id', 'jenis'], 'tautan_berkas_pemilik_jenis_index');
        });

        // Indeks prefiks 191 karakter (kolom URL panjang) hanya didukung lewat SQL mentah.
        DB::statement('CREATE INDEX tautan_berkas_url_normal_index ON tautan_berkas (url_normal(191))');
    }

    public function down(): void
    {
        Schema::dropIfExists('tautan_berkas');
    }
};

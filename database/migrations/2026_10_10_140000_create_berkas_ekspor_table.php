<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hanya ekspor besar (antrean): berkas di storage/app/tmp/ekspor, dihapus ≤ 24 jam. Ekspor kecil di-stream tanpa baris di sini.
        Schema::create('berkas_ekspor', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('jenis', 30);
            $table->string('format', 10);
            $table->json('parameter')->nullable();
            $table->string('path')->nullable();
            $table->string('status', 20)->default('antre');
            $table->unsignedInteger('jumlah_baris')->nullable();
            $table->text('pesan_galat')->nullable();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('kedaluwarsa_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berkas_ekspor');
    }
};

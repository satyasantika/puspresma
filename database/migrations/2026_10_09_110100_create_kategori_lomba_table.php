<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_lomba', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // NULL = berlaku untuk semua jenis kegiatan.
            $table->foreignUuid('jenis_kegiatan_id')->nullable()->constrained('jenis_kegiatan')->restrictOnDelete();
            $table->string('nama', 100);
            $table->string('kode_simkatmawa', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['jenis_kegiatan_id', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_lomba');
    }
};

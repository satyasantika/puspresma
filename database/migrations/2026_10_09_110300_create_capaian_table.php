<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capaian', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('jenis_kegiatan_id')->constrained('jenis_kegiatan')->restrictOnDelete();
            $table->string('kode', 40);
            $table->string('nama', 100);
            $table->boolean('is_juara')->default(false);
            $table->smallInteger('urutan')->default(0);
            $table->string('kode_simkatmawa', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['jenis_kegiatan_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capaian');
    }
};

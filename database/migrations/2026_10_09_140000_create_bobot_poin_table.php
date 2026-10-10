<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bobot_poin', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('jenis_kegiatan_id')->constrained('jenis_kegiatan')->restrictOnDelete();
            $table->foreignUuid('tingkat_id')->constrained('tingkat')->restrictOnDelete();
            $table->foreignUuid('capaian_id')->constrained('capaian')->restrictOnDelete();
            $table->decimal('poin', 8, 2)->default(0);
            $table->date('berlaku_mulai');
            $table->date('berlaku_sampai')->nullable();
            $table->string('dasar_penetapan')->nullable();
            $table->timestamps();

            $table->unique(['jenis_kegiatan_id', 'tingkat_id', 'capaian_id', 'berlaku_mulai'], 'bobot_poin_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bobot_poin');
    }
};

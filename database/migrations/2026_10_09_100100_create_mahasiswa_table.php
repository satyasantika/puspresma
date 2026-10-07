<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('npm', 20)->unique();
            $table->string('nama', 150)->index();
            $table->foreignUuid('prodi_id')->constrained('prodi')->restrictOnDelete();
            $table->unsignedSmallInteger('angkatan');
            $table->string('email', 191)->nullable()->unique();
            $table->string('nomor_hp', 20)->nullable();
            $table->string('status_mahasiswa', 20)->default('aktif')->index();
            $table->foreignUuid('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('kode_eksternal', 50)->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['prodi_id', 'angkatan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasiswa');
    }
};

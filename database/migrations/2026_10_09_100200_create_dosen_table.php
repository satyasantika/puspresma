<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dosen', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nidn', 20)->nullable()->unique();
            $table->string('nip', 30)->nullable()->unique();
            $table->string('nama', 150)->index();
            $table->string('gelar_depan', 30)->nullable();
            $table->string('gelar_belakang', 60)->nullable();
            $table->foreignUuid('prodi_id')->nullable()->constrained('prodi')->nullOnDelete();
            $table->string('email', 191)->nullable()->unique();
            $table->foreignUuid('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('kode_eksternal', 50)->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dosen');
    }
};

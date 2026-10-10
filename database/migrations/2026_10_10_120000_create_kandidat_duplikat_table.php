<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kandidat_duplikat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('prestasi_id')->constrained('prestasi')->cascadeOnDelete();
            $table->foreignUuid('pembanding_id')->constrained('prestasi')->cascadeOnDelete();
            $table->string('alasan', 30);
            $table->decimal('skor', 5, 2);
            $table->string('status_tinjau', 20)->default('belum_ditinjau')->index();
            $table->foreignUuid('ditinjau_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('ditinjau_at')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['prestasi_id', 'pembanding_id', 'alasan'], 'kandidat_duplikat_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kandidat_duplikat');
    }
};

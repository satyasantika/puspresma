<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestasi', function (Blueprint $table) {
            $table->foreign('kompetisi_id')->references('id')->on('kompetisi')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prestasi', function (Blueprint $table) {
            $table->dropForeign(['kompetisi_id']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestasi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nomor_registrasi', 20)->nullable()->unique();
            $table->foreignUuid('pengaju_id')->constrained('mahasiswa')->restrictOnDelete();
            $table->foreignUuid('prodi_id')->constrained('prodi')->restrictOnDelete();
            $table->foreignUuid('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('jenis_kegiatan_id')->constrained('jenis_kegiatan')->restrictOnDelete();
            $table->string('bidang', 20)->index();
            $table->foreignUuid('kategori_lomba_id')->nullable()->constrained('kategori_lomba')->nullOnDelete();
            $table->foreignUuid('tingkat_id')->constrained('tingkat')->restrictOnDelete();
            $table->foreignUuid('capaian_id')->constrained('capaian')->restrictOnDelete();
            $table->string('capaian_keterangan', 150)->nullable();
            $table->string('bentuk', 20)->default('individu');
            $table->string('nama_kegiatan', 255);
            $table->string('nama_kegiatan_normal', 255)->index();
            $table->string('judul_karya', 255)->nullable();
            $table->string('penyelenggara', 255);
            $table->string('url_kegiatan', 500)->nullable();
            $table->date('tanggal_mulai')->index();
            $table->date('tanggal_selesai');
            $table->unsignedSmallInteger('tahun')->index();
            $table->string('moda', 10)->default('luring');
            $table->string('lokasi', 150)->nullable();
            $table->string('negara_penyelenggara', 100)->default('Indonesia');
            $table->unsignedInteger('jumlah_peserta')->nullable();
            $table->unsignedInteger('jumlah_pt_peserta')->nullable();
            $table->unsignedSmallInteger('jumlah_negara_peserta')->nullable();
            $table->text('negara_peserta')->nullable();
            $table->foreignUuid('dosen_pembimbing_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->string('nama_pembimbing_lain', 150)->nullable();
            $table->string('status_konfirmasi_pembimbing', 20)->default('tidak_ada');
            $table->text('catatan_pembimbing')->nullable();
            $table->dateTime('dikonfirmasi_pembimbing_at')->nullable();
            $table->string('nomor_sertifikat', 100)->nullable();
            $table->text('deskripsi')->nullable();
            // FK ke kompetisi ditambahkan di F6.1 (tabel belum ada).
            $table->uuid('kompetisi_id')->nullable();
            $table->string('status', 20)->default('draf')->index();
            $table->dateTime('diajukan_at')->nullable();
            $table->dateTime('diverifikasi_at')->nullable();
            $table->foreignUuid('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_verifikasi')->nullable();
            $table->boolean('verifikasi_tanpa_konfirmasi')->default(false);
            $table->boolean('disembunyikan_dari_galeri')->default(false);
            $table->string('alasan_disembunyikan', 255)->nullable();
            $table->dateTime('dilaporkan_simkatmawa_at')->nullable()->index();
            $table->foreignUuid('dilaporkan_simkatmawa_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['prodi_id', 'status']);
            $table->index(['tahun', 'tingkat_id']);
            $table->index(['dosen_pembimbing_id', 'status_konfirmasi_pembimbing'], 'prestasi_pembimbing_status_index');
            $table->fullText(['nama_kegiatan', 'penyelenggara']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestasi');
    }
};

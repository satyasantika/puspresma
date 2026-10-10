<?php

/*
|--------------------------------------------------------------------------
| Pemetaan ekspor SIMKATMAWA
|--------------------------------------------------------------------------
|
| Format kolom resmi SIMKATMAWA PERLU DIVERIFIKASI; karena itu urutan dan header kolom dapat diubah di sini
| tanpa mengubah kode. Nilai = kunci sumber dari App\Services\PemetaanSimkatmawa. Sesuaikan dengan templat
| isian SIMKATMAWA terbaru (perlu verifikasi).
|
*/

return [

    /** 'anggota' = satu baris per prestasi × anggota internal; 'prestasi' = satu baris per prestasi (pengaju). Perlu verifikasi. */
    'satu_baris_per' => 'anggota',

    'kolom' => [
        'Nomor Registrasi' => 'prestasi.nomor_registrasi',
        'NPM' => 'mahasiswa.npm',
        'Nama Mahasiswa' => 'mahasiswa.nama',
        'Kode PDDikti Prodi' => 'prodi.kode_pddikti',
        'Program Studi' => 'prodi.nama',
        'Nama Kegiatan' => 'kegiatan.nama',
        'Penyelenggara' => 'kegiatan.penyelenggara',
        'Jenis Kegiatan' => 'jenis.kode_simkatmawa',
        'Kategori' => 'kategori.kode_simkatmawa',
        'Tingkat' => 'tingkat.kode_simkatmawa',
        'Capaian' => 'capaian.kode_simkatmawa',
        'Bentuk Partisipasi' => 'bentuk',
        'Tanggal Mulai' => 'kegiatan.tanggal_mulai',
        'Tanggal Selesai' => 'kegiatan.tanggal_selesai',
        'Tautan Kegiatan' => 'kegiatan.url',
        'Jumlah Peserta' => 'kegiatan.jumlah_peserta',
        'Jumlah Perguruan Tinggi' => 'kegiatan.jumlah_pt',
        'Jumlah Negara' => 'kegiatan.jumlah_negara',
        'NIDN Pembimbing' => 'pembimbing.nidn',
        'Nama Pembimbing' => 'pembimbing.nama',
        'URL Sertifikat' => 'bukti.url_sertifikat',
        'URL Foto' => 'bukti.url_foto',
        'URL Surat Tugas' => 'bukti.url_surat_tugas',
        'Status Tautan' => 'meta.status_tautan',
        'Catatan Pemetaan' => 'meta.catatan_pemetaan',
    ],

];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Domain surel kampus
    |--------------------------------------------------------------------------
    |
    | Dipakai untuk mencocokkan akun mahasiswa/pegawai (mis. login Google, BR-20).
    |
    */

    'domain_mahasiswa' => env('PUSPRESMA_DOMAIN_MAHASISWA', 'student.unsil.ac.id'),
    'domain_pegawai' => env('PUSPRESMA_DOMAIN_PEGAWAI', 'unsil.ac.id'),

    /*
    |--------------------------------------------------------------------------
    | Impor master data
    |--------------------------------------------------------------------------
    |
    | Satu-satunya input berkas: Excel/CSV master yang diproses lalu dihapus.
    | Bukti prestasi berupa tautan (tabel tautan_berkas), bukan unggahan.
    |
    */

    'impor' => [
        'mimes' => ['xlsx', 'csv'],
        'maks_kb' => 10240,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ekspor
    |--------------------------------------------------------------------------
    |
    | Batas baris ekspor yang di-stream langsung; di atasnya lewat antrean `ekspor` (BR-25).
    |
    */

    'ekspor' => [
        'batas_stream' => (int) env('EKSPOR_BATAS_STREAM', 2000),
        'chunk' => 500,
    ],

    /** Jumlah prestasi yang dibuat DemoSeeder (lokal/uji kinerja saja). */
    'demo_prestasi' => (int) env('DEMO_PRESTASI', 60),
    'panduan_password' => env('PANDUAN_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Nama antrean
    |--------------------------------------------------------------------------
    */

    'antrean' => [
        'notifikasi' => 'notifikasi',
        'ekspor' => 'ekspor',
        'impor' => 'impor',
        'berkas' => 'berkas',
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Google akun kampus (opsional)
    |--------------------------------------------------------------------------
    */

    'google_login' => (bool) env('GOOGLE_LOGIN_ENABLED', false),

    /**
     * Pola NPM dari bagian lokal surel mahasiswa (cadangan bila surel belum tercatat di master).
     * Grup 1 = NPM. Pola ini perlu diverifikasi terhadap format surel kampus.
     */
    'google_npm_pola' => env('GOOGLE_NPM_POLA', '/^(\\d{8,20})$/'),

    /*
    |--------------------------------------------------------------------------
    | Akun super-admin awal (SuperAdminSeeder)
    |--------------------------------------------------------------------------
    */

    'seed_superadmin' => [
        'email' => env('SEED_SUPERADMIN_EMAIL'),
        'name' => env('SEED_SUPERADMIN_NAME', 'Super Admin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Peran yang wajib MFA aplikasi authenticator (TOTP)
    |--------------------------------------------------------------------------
    */

    'peran_wajib_mfa' => ['super-admin', 'admin-fakultas'],
];

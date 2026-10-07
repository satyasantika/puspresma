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

    /*
    |--------------------------------------------------------------------------
    | Akun super-admin awal (SuperAdminSeeder)
    |--------------------------------------------------------------------------
    */

    'seed_superadmin' => [
        'email' => env('SEED_SUPERADMIN_EMAIL'),
        'name' => env('SEED_SUPERADMIN_NAME', 'Super Admin'),
    ],
];

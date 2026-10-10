<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mode berkas (STANDAR-TEKNIS §1a)
    |--------------------------------------------------------------------------
    |
    | 'tautan' = tidak ada unggahan ke server; bukti disimpan sebagai URL.
    | Mode lain belum didukung dan melempar exception saat PenyimpananBerkas dibuat.
    |
    */

    'mode' => env('BERKAS_MODE', 'tautan'),

    /*
    |--------------------------------------------------------------------------
    | Domain yang diizinkan (awalan *. = subdomain)
    |--------------------------------------------------------------------------
    */

    'domain_diizinkan' => [
        'drive.google.com',
        'docs.google.com',
        '*.unsil.ac.id',
        // 'onedrive.live.com',
        // '*.sharepoint.com',
    ],

    /** Pemendek URL yang ditolak (tujuan akhir tidak dapat dinilai). */
    'pemendek_ditolak' => ['bit.ly', 's.id', 'tinyurl.com', 'goo.gl', 't.co', 'cutt.ly', 'rebrand.ly'],

    /** Batas waktu pemeriksaan keteraksesan (detik). */
    'timeout_cek' => 10,

    /** Maksimal pengalihan yang diikuti pemeriksa. */
    'maks_redirect' => 3,

];

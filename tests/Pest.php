<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Manifest Vite (public/build) tidak diperlukan oleh test.
pest()->extend(TestCase::class)->beforeEach(fn () => $this->withoutVite())->in('Feature');
pest()->extend(TestCase::class)->in('Arch');
pest()->extend(PHPUnit\Framework\TestCase::class)->in('Unit');

// Test fitur di folder bertabel (semua folder domain di bawah) memakai basis data uji yang selalu bersih.
pest()->use(RefreshDatabase::class)->in(
    'Feature/Auth', 'Feature/Master', 'Feature/Impor', 'Feature/Pengaturan', 'Feature/Poin', 'Feature/Berkas',
    'Feature/Prestasi', 'Feature/Verifikasi', 'Feature/Alur', 'Feature/Katalog', 'Feature/Galeri',
    'Feature/Notifikasi', 'Feature/Jadwal', 'Feature/Laporan', 'Feature/Surat', 'Feature/Keamanan', 'Feature/Kinerja',
);

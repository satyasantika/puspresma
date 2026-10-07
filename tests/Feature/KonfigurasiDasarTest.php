<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class KonfigurasiDasarTest extends TestCase
{
    public function test_memakai_zona_waktu_asia_jakarta(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', now()->getTimezone()->getName());
    }

    public function test_memakai_locale_indonesia(): void
    {
        $this->assertSame('id', config('app.locale'));
        $this->assertSame('id_ID', config('app.faker_locale'));
        $this->assertSame('07 Oktober 2026', now()->setDate(2026, 10, 7)->translatedFormat('d F Y'));
    }

    public function test_memuat_konfigurasi_puspresma(): void
    {
        $this->assertSame(10240, config('puspresma.impor.maks_kb'));
        $this->assertSame(['xlsx', 'csv'], config('puspresma.impor.mimes'));
        $this->assertSame('student.unsil.ac.id', config('puspresma.domain_mahasiswa'));
        $this->assertSame('unsil.ac.id', config('puspresma.domain_pegawai'));
        $this->assertSame('ekspor', config('puspresma.antrean.ekspor'));
        $this->assertFalse(config('puspresma.google_login'));
    }

    public function test_menyediakan_versi_aplikasi(): void
    {
        $this->assertIsString(config('app.version'));
        $this->assertNotSame('', config('app.version'));
    }

    public function test_pesan_validasi_berbahasa_indonesia(): void
    {
        $validator = Validator::make([], ['nama' => 'required']);

        $this->assertSame('Nama wajib diisi.', $validator->errors()->first('nama'));
    }
}

<?php

use Illuminate\Support\Facades\Validator;

it('memakai zona waktu Asia/Jakarta', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta')
        ->and(now()->getTimezone()->getName())->toBe('Asia/Jakarta');
});

it('memakai locale Indonesia', function () {
    expect(config('app.locale'))->toBe('id')
        ->and(config('app.faker_locale'))->toBe('id_ID')
        ->and(now()->setDate(2026, 10, 7)->translatedFormat('d F Y'))->toBe('07 Oktober 2026');
});

it('memuat konfigurasi puspresma', function () {
    expect(config('puspresma.impor.maks_kb'))->toBe(10240)
        ->and(config('puspresma.impor.mimes'))->toBe(['xlsx', 'csv'])
        ->and(config('puspresma.domain_mahasiswa'))->toBe('student.unsil.ac.id')
        ->and(config('puspresma.domain_pegawai'))->toBe('unsil.ac.id')
        ->and(config('puspresma.antrean.ekspor'))->toBe('berat')
        ->and(config('puspresma.google_login'))->toBeFalse();
});

it('menyediakan versi aplikasi', function () {
    expect(config('app.version'))->toBeString()->not->toBeEmpty();
});

it('menampilkan pesan validasi berbahasa Indonesia', function () {
    $validator = Validator::make([], ['nama' => 'required']);

    expect($validator->errors()->first('nama'))->toBe('Nama wajib diisi.');
});

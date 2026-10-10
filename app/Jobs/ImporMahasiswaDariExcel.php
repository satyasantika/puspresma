<?php

namespace App\Jobs;

use App\Enums\StatusMahasiswa;
use App\Models\Mahasiswa;
use Illuminate\Validation\Rule;

/** Impor master mahasiswa; kunci upsert = NPM (BR-19). */
class ImporMahasiswaDariExcel extends ImporMasterDariExcel
{
    protected function modelClass(): string
    {
        return Mahasiswa::class;
    }

    protected function namaLock(): string
    {
        return 'lock:impor:mahasiswa';
    }

    protected function aturan(): array
    {
        return [
            'npm' => ['required', 'regex:/^\d{8,20}$/'],
            'nama' => ['required', 'max:150'],
            'kode_prodi' => ['required', Rule::in(array_keys($this->petaProdi()))],
            'angkatan' => ['required', 'integer', 'digits:4', 'between:1990,'.date('Y')],
            'email' => ['nullable', 'email', 'max:191', 'ends_with:@'.config('puspresma.domain_mahasiswa')],
            'status_mahasiswa' => ['nullable', Rule::enum(StatusMahasiswa::class)],
        ];
    }

    protected function kolomUnik(): array
    {
        return ['npm', 'email'];
    }

    /** @param  array<string, mixed>  $baris */
    protected function kolomKunci(array $baris): string
    {
        return 'npm';
    }

    protected function petakan(array $baris): array
    {
        return [
            'npm' => $baris['npm'],
            'nama' => $baris['nama'],
            'prodi_id' => $this->petaProdi()[strtoupper($baris['kode_prodi'])],
            'angkatan' => (int) $baris['angkatan'],
            'email' => $baris['email'] ?? null,
            'status_mahasiswa' => $baris['status_mahasiswa'] ?? null,
        ];
    }
}

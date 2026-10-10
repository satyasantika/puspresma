<?php

namespace App\Jobs;

use App\Models\Dosen;
use Illuminate\Validation\Rule;

/** Impor master dosen; kunci upsert = NIDN bila ada, jika tidak NIP. */
class ImporDosenDariExcel extends ImporMasterDariExcel
{
    protected function modelClass(): string
    {
        return Dosen::class;
    }

    protected function namaLock(): string
    {
        return 'lock:impor:dosen';
    }

    protected function aturan(): array
    {
        return [
            'nidn' => ['nullable', 'regex:/^\d{5,20}$/', 'required_without:nip'],
            'nip' => ['nullable', 'regex:/^\d{8,30}$/', 'required_without:nidn'],
            'nama' => ['required', 'max:150'],
            'gelar_depan' => ['nullable', 'max:30'],
            'gelar_belakang' => ['nullable', 'max:60'],
            'kode_prodi' => ['nullable', Rule::in(array_keys($this->petaProdi()))],
            'email' => ['nullable', 'email', 'max:191', 'ends_with:@'.config('puspresma.domain_pegawai')],
        ];
    }

    protected function kolomUnik(): array
    {
        return ['nidn', 'nip', 'email'];
    }

    /** @param  array<string, mixed>  $baris */
    protected function kolomKunci(array $baris): ?string
    {
        return filled($baris['nidn'] ?? null) ? 'nidn' : (filled($baris['nip'] ?? null) ? 'nip' : null);
    }

    protected function petakan(array $baris): array
    {
        return [
            'nidn' => $baris['nidn'] ?? null,
            'nip' => $baris['nip'] ?? null,
            'nama' => $baris['nama'],
            'gelar_depan' => $baris['gelar_depan'] ?? null,
            'gelar_belakang' => $baris['gelar_belakang'] ?? null,
            'prodi_id' => filled($baris['kode_prodi'] ?? null) ? $this->petaProdi()[strtoupper($baris['kode_prodi'])] : null,
            'email' => $baris['email'] ?? null,
        ];
    }
}

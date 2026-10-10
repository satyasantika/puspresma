<?php

namespace App\Actions\Surat;

use App\Enums\StatusSurat;
use App\Models\SuratKeteranganPrestasi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CabutSuratKeteranganPrestasi
{
    public function jalankan(SuratKeteranganPrestasi $surat, User $pelaku, string $alasan): SuratKeteranganPrestasi
    {
        if (! $pelaku->can('surat.terbitkan')) {
            throw new AuthorizationException('Anda tidak berhak mencabut surat keterangan.');
        }

        if (trim($alasan) === '') {
            throw ValidationException::withMessages(['alasan' => 'Alasan pencabutan wajib diisi.']);
        }

        $surat->update(['status' => StatusSurat::Dicabut, 'dicabut_at' => now(), 'alasan_dicabut' => mb_substr(trim($alasan), 0, 255)]);

        // Halaman verifikasi publik langsung menampilkan "Dicabut".
        Cache::forget("verifikasi-surat:{$surat->getKey()}");

        return $surat;
    }
}

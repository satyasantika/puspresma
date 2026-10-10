<?php

namespace App\Actions\Prestasi;

use App\Enums\StatusPrestasi;
use App\Models\Prestasi;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/** Tahap 1 verifikasi oleh admin-prodi pengaju bila `verifikasi_prodi_aktif` (BR-09): diajukan → lolos_prodi. */
class LoloskanTahapProdi
{
    public function __construct(private readonly TransisiStatus $transisi) {}

    public function jalankan(Prestasi $prestasi, User $pelaku, ?string $catatan = null): Prestasi
    {
        Gate::forUser($pelaku)->authorize('loloskanProdi', $prestasi);

        return $this->transisi->ubah($prestasi, StatusPrestasi::LolosProdi, $pelaku, $catatan, ['catatan_verifikasi' => filled($catatan) ? trim($catatan) : null]);
    }
}

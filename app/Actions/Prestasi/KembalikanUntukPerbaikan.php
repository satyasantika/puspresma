<?php

namespace App\Actions\Prestasi;

use App\Enums\StatusPrestasi;
use App\Models\Prestasi;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/** diajukan/lolos_prodi → perlu_perbaikan dengan catatan ≥ 10 karakter (BR-08). */
class KembalikanUntukPerbaikan
{
    public function __construct(private readonly TransisiStatus $transisi) {}

    public function jalankan(Prestasi $prestasi, User $pelaku, string $catatan): Prestasi
    {
        Gate::forUser($pelaku)->authorize('kembalikan', $prestasi);

        return $this->transisi->ubah($prestasi, StatusPrestasi::PerluPerbaikan, $pelaku, $catatan, ['catatan_verifikasi' => trim($catatan)]);
    }
}

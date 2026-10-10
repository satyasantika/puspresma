<?php

namespace App\Actions\Prestasi;

use App\Enums\StatusPrestasi;
use App\Models\Prestasi;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/** diajukan/lolos_prodi → ditolak (final, BR-13) dengan catatan ≥ 10 karakter. */
class TolakPrestasi
{
    public function __construct(private readonly TransisiStatus $transisi) {}

    public function jalankan(Prestasi $prestasi, User $pelaku, string $catatan): Prestasi
    {
        Gate::forUser($pelaku)->authorize('tolak', $prestasi);

        return $this->transisi->ubah($prestasi, StatusPrestasi::Ditolak, $pelaku, $catatan, ['catatan_verifikasi' => trim($catatan)]);
    }
}

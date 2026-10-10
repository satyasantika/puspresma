<?php

namespace App\Actions\Prestasi;

use App\Enums\StatusKonfirmasiPembimbing;
use App\Enums\StatusPrestasi;
use App\Models\Prestasi;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/** Pengaju menarik pengajuan kembali ke draf selama belum diproses verifikator (diajukan → draf). */
class TarikKembaliPrestasi
{
    public function __construct(private readonly TransisiStatus $transisi) {}

    public function jalankan(Prestasi $prestasi, User $pelaku): Prestasi
    {
        Gate::forUser($pelaku)->authorize('tarikKembali', $prestasi);

        $tambahan = $prestasi->status_konfirmasi_pembimbing === StatusKonfirmasiPembimbing::Menunggu
            ? ['status_konfirmasi_pembimbing' => StatusKonfirmasiPembimbing::TidakAda]
            : [];

        return $this->transisi->ubah($prestasi, StatusPrestasi::Draf, $pelaku, 'Ditarik kembali oleh pengaju.', $tambahan);
    }
}

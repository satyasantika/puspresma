<?php

namespace App\Actions\Pembimbing;

use App\Enums\StatusKonfirmasiPembimbing;
use App\Events\PembimbingMenjawab;
use App\Models\Prestasi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Dosen menyangkal pembimbingan dengan alasan ≥ 10 karakter: menunggu → disangkal (memblokir verifikasi). */
class SangkalPembimbingan
{
    public function jalankan(Prestasi $prestasi, User $dosen, string $alasan): Prestasi
    {
        Gate::forUser($dosen)->authorize('jawabPembimbing', $prestasi);

        if (mb_strlen(trim($alasan)) < 10) {
            throw ValidationException::withMessages(['alasan' => 'Alasan wajib diisi minimal 10 karakter.']);
        }

        DB::transaction(function () use ($prestasi, $alasan): void {
            $terkunci = Prestasi::whereKey($prestasi->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($terkunci->status_konfirmasi_pembimbing === StatusKonfirmasiPembimbing::Menunggu, 409, 'Permintaan konfirmasi sudah dijawab.');

            $terkunci->forceFill([
                'status_konfirmasi_pembimbing' => StatusKonfirmasiPembimbing::Disangkal,
                'dikonfirmasi_pembimbing_at' => now(),
                'catatan_pembimbing' => trim($alasan),
            ])->save();
            $prestasi->setRawAttributes($terkunci->getAttributes(), true)->syncOriginal();
        });

        PembimbingMenjawab::dispatch($prestasi, $dosen, false);

        return $prestasi;
    }
}

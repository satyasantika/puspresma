<?php

namespace App\Actions\Pembimbing;

use App\Enums\StatusKonfirmasiPembimbing;
use App\Events\PembimbingMenjawab;
use App\Models\Prestasi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Dosen yang ditunjuk membenarkan pembimbingan (BR-11): menunggu → dikonfirmasi. */
class KonfirmasiPembimbingan
{
    public function jalankan(Prestasi $prestasi, User $dosen): Prestasi
    {
        Gate::forUser($dosen)->authorize('jawabPembimbing', $prestasi);

        DB::transaction(function () use ($prestasi): void {
            // Baca ulang dengan kunci agar jawaban ganda/basi tidak menimpa.
            $terkunci = Prestasi::whereKey($prestasi->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($terkunci->status_konfirmasi_pembimbing === StatusKonfirmasiPembimbing::Menunggu, 409, 'Permintaan konfirmasi sudah dijawab.');

            $terkunci->forceFill([
                'status_konfirmasi_pembimbing' => StatusKonfirmasiPembimbing::Dikonfirmasi,
                'dikonfirmasi_pembimbing_at' => now(),
                'catatan_pembimbing' => null,
            ])->save();
            $prestasi->setRawAttributes($terkunci->getAttributes(), true)->syncOriginal();
        });

        PembimbingMenjawab::dispatch($prestasi, $dosen, true);

        return $prestasi;
    }
}

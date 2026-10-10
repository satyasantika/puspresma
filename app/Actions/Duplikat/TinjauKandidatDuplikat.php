<?php

namespace App\Actions\Duplikat;

use App\Enums\StatusTinjauDuplikat;
use App\Models\KandidatDuplikat;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Admin menandai kandidat "bukan duplikat" atau "duplikat" (catatan wajib untuk duplikat). */
class TinjauKandidatDuplikat
{
    public function jalankan(KandidatDuplikat $kandidat, User $pelaku, StatusTinjauDuplikat $status, ?string $catatan = null): KandidatDuplikat
    {
        Gate::forUser($pelaku)->authorize('tinjauDuplikat', $kandidat);

        if ($status === StatusTinjauDuplikat::BelumDitinjau) {
            throw ValidationException::withMessages(['status' => 'Pilih "bukan duplikat" atau "duplikat".']);
        }

        if ($status === StatusTinjauDuplikat::Duplikat && mb_strlen(trim((string) $catatan)) < 10) {
            throw ValidationException::withMessages(['catatan' => 'Catatan wajib diisi minimal 10 karakter untuk status duplikat.']);
        }

        $kandidat->update([
            'status_tinjau' => $status,
            'ditinjau_oleh' => $pelaku->getKey(),
            'ditinjau_at' => now(),
            'catatan' => filled($catatan) ? trim($catatan) : null,
        ]);

        return $kandidat;
    }
}

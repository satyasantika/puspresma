<?php

namespace App\Actions\Simkatmawa;

use App\Models\Prestasi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Menandai prestasi terverifikasi sebagai sudah dilaporkan ke SIMKATMAWA (BR-27); tercatat di audit. */
class TandaiDilaporkanSimkatmawa
{
    /**
     * @param  iterable<Prestasi>  $prestasi
     * @return int jumlah yang ditandai
     */
    public function jalankan(iterable $prestasi, User $pelaku): int
    {
        $daftar = collect($prestasi);

        foreach ($daftar as $p) {
            Gate::forUser($pelaku)->authorize('tandaiSimkatmawa', $p);
        }

        return DB::transaction(function () use ($daftar, $pelaku): int {
            $jumlah = 0;
            foreach ($daftar as $p) {
                if ($p->dilaporkan_simkatmawa_at !== null) {
                    continue;
                }
                $p->forceFill(['dilaporkan_simkatmawa_at' => now(), 'dilaporkan_simkatmawa_oleh' => $pelaku->getKey()])->save();
                $jumlah++;
            }

            return $jumlah;
        });
    }

    public function batalkan(Prestasi $prestasi, User $pelaku, string $catatan): Prestasi
    {
        Gate::forUser($pelaku)->authorize('tandaiSimkatmawa', $prestasi);

        if (mb_strlen(trim($catatan)) < 10) {
            throw ValidationException::withMessages(['catatan' => 'Catatan pembatalan wajib diisi minimal 10 karakter.']);
        }

        $prestasi->forceFill(['dilaporkan_simkatmawa_at' => null, 'dilaporkan_simkatmawa_oleh' => null])->save();
        activity('simkatmawa')->performedOn($prestasi)->causedBy($pelaku)->event('dibatalkan')->withProperties(['catatan' => trim($catatan)])->log('Penandaan SIMKATMAWA dibatalkan');

        return $prestasi;
    }
}

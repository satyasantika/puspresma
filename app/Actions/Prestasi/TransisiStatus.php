<?php

namespace App\Actions\Prestasi;

use App\Enums\StatusPrestasi;
use App\Events\StatusPrestasiBerubah;
use App\Exceptions\TransisiTidakSah;
use App\Models\Prestasi;
use App\Models\RiwayatStatusPrestasi;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya jalan mengubah status prestasi (BR-07): lock Redis + lockForUpdate, tabel transisi sah,
 * catatan wajib (BR-08), riwayat, activitylog (lewat model), dan event setelah commit.
 */
class TransisiStatus
{
    /**
     * @param  array<string, mixed>  $tambahan  kolom lain yang harus berubah atomik bersama status
     */
    public function ubah(Prestasi $prestasi, StatusPrestasi $ke, User $pelaku, ?string $catatan = null, array $tambahan = []): Prestasi
    {
        $hasil = Cache::lock("lock:prestasi:{$prestasi->getKey()}:status", 10)->block(5, function () use ($prestasi, $ke, $pelaku, $catatan, $tambahan): array {
            return DB::transaction(function () use ($prestasi, $ke, $pelaku, $catatan, $tambahan): array {
                $terkunci = Prestasi::whereKey($prestasi->getKey())->lockForUpdate()->firstOrFail();
                $dari = $terkunci->status;

                if (! $dari->bolehKe($ke)) {
                    throw new TransisiTidakSah($dari, $ke);
                }

                if ($dari->wajibCatatanKe($ke) && mb_strlen(trim((string) $catatan)) < 10) {
                    throw ValidationException::withMessages(['catatan' => 'Catatan wajib diisi minimal 10 karakter.']);
                }

                $terkunci->forceFill($tambahan + ['status' => $ke])->save();

                RiwayatStatusPrestasi::create([
                    'prestasi_id' => $terkunci->id,
                    'dari_status' => $dari,
                    'ke_status' => $ke,
                    'user_id' => $pelaku->id,
                    'catatan' => filled($catatan) ? trim($catatan) : null,
                ]);

                return [$terkunci, $dari];
            });
        });

        [$terkunci, $dari] = $hasil;
        StatusPrestasiBerubah::dispatch($terkunci, $dari, $ke);

        // Sinkronkan instance pemanggil dengan data terbaru.
        return $prestasi->setRawAttributes($terkunci->getAttributes(), true)->syncOriginal();
    }
}

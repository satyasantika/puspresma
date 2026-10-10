<?php

namespace App\Actions\Poin;

use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use App\Services\Pengaturan;
use App\Services\PenghitungPoin;
use Illuminate\Support\Facades\DB;

/**
 * Snapshot poin per anggota internal saat terverifikasi (BR-18). Aritmetika string/bcmath, tanpa float.
 * Mode `penuh`: tiap anggota menerima bobot penuh; `dibagi`: bobot ÷ jumlah anggota internal (skala 2).
 */
class HitungPoinPrestasi
{
    public function __construct(private readonly PenghitungPoin $penghitung) {}

    /** @return bool true bila poin disimpan (poin_aktif) */
    public function jalankan(Prestasi $prestasi): bool
    {
        if (! Pengaturan::ambil('poin_aktif', false)) {
            return false;
        }

        $bobot = $this->penghitung->bobot($prestasi->jenis_kegiatan_id, $prestasi->tingkat_id, $prestasi->capaian_id, $prestasi->tanggal_selesai);

        if ($bobot === null) {
            $bobot = '0.00';
            activity('poin')->performedOn($prestasi)->event('bobot_tidak_ditemukan')->log('Bobot poin tidak ditemukan; poin diisi 0.00');
        }

        DB::transaction(function () use ($prestasi, $bobot): void {
            $anggota = PrestasiAnggota::where('prestasi_id', $prestasi->id)->get();
            $poin = Pengaturan::ambil('mode_poin_tim', 'penuh') === 'dibagi' && $anggota->count() > 0
                ? bcdiv($bobot, (string) $anggota->count(), 2)
                : bcadd($bobot, '0', 2);

            $anggota->each(fn (PrestasiAnggota $a) => $a->update(['poin' => $poin]));
        });

        return true;
    }
}

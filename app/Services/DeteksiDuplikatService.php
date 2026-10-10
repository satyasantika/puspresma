<?php

namespace App\Services;

use App\Enums\AlasanDuplikat;
use App\Enums\StatusPrestasi;
use App\Models\Prestasi;
use App\Models\TautanBerkas;
use Illuminate\Support\Collection;

/**
 * Mencari kandidat duplikat prestasi (BR-10): (a) anggota sama + nama kegiatan mirip + tanggal berdekatan,
 * atau (b) tautan bukti sama (drive_file_id, atau url_normal untuk penyedia non-Drive).
 */
class DeteksiDuplikatService
{
    /**
     * @return Collection<int, array{pembanding_id: string, alasan: AlasanDuplikat, skor: string}>
     */
    public function cari(Prestasi $prestasi): Collection
    {
        return $this->kegiatanMirip($prestasi)->concat($this->tautanSama($prestasi))->values();
    }

    /** @return Collection<int, array{pembanding_id: string, alasan: AlasanDuplikat, skor: string}> */
    private function kegiatanMirip(Prestasi $prestasi): Collection
    {
        $anggotaIds = $prestasi->anggota()->pluck('mahasiswa_id')->all();
        if ($anggotaIds === []) {
            return collect();
        }

        $ambang = (float) Pengaturan::ambil('ambang_kemiripan_duplikat', '0.80');
        $rentang = (int) Pengaturan::ambil('rentang_hari_duplikat', 7);
        $mulai = $prestasi->tanggal_mulai;

        // Anggota yang sama + selisih tanggal sudah mempersempit kandidat; FULLTEXT tidak diperlukan pada skala fakultas.
        // @phpstan-ignore return.type (tipe literal enum/numeric-string melebar ke tipe docblock)
        return Prestasi::query()
            ->whereKeyNot($prestasi->getKey())
            ->where('status', '!=', StatusPrestasi::Draf->value)
            ->whereBetween('tanggal_mulai', [$mulai->copy()->subDays($rentang)->toDateString(), $mulai->copy()->addDays($rentang)->toDateString()])
            ->whereHas('anggota', fn ($q) => $q->whereIn('mahasiswa_id', $anggotaIds))
            ->get(['id', 'nama_kegiatan_normal'])
            ->map(function (Prestasi $lain) use ($prestasi): array {
                similar_text($prestasi->nama_kegiatan_normal, $lain->nama_kegiatan_normal, $persen);

                return ['pembanding_id' => $lain->id, 'alasan' => AlasanDuplikat::KegiatanMirip, 'skor' => number_format($persen, 2, '.', '')];
            })
            ->filter(fn (array $k) => (float) $k['skor'] / 100 >= $ambang)
            ->values();
    }

    /** @return Collection<int, array{pembanding_id: string, alasan: AlasanDuplikat, skor: string}> */
    private function tautanSama(Prestasi $prestasi): Collection
    {
        $hasil = collect();

        foreach ($prestasi->tautanBukti()->get() as $tautan) {
            $lain = TautanBerkas::query()
                ->where('pemilik_type', $prestasi->getMorphClass())
                ->where('pemilik_id', '!=', $prestasi->getKey())
                ->where('jenis', '!=', 'poster')
                ->when(
                    $tautan->drive_file_id !== null,
                    fn ($q) => $q->where('drive_file_id', $tautan->drive_file_id),
                    fn ($q) => $q->whereNull('drive_file_id')->where('url_normal', $tautan->url_normal),
                )
                ->pluck('pemilik_id');

            Prestasi::query()->whereIn('id', $lain)->where('status', '!=', StatusPrestasi::Draf->value)->pluck('id')
                ->each(fn (string $id) => $hasil->push(['pembanding_id' => $id, 'alasan' => AlasanDuplikat::TautanSama, 'skor' => '100.00']));
        }

        return $hasil->unique('pembanding_id')->values();
    }
}

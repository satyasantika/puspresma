<?php

namespace App\Services;

use App\Models\BobotPoin;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

/** Bobot poin per jenis × tingkat × capaian pada suatu tanggal (BR-18); nilai selalu string desimal. */
class PenghitungPoin
{
    public const KUNCI_CACHE = 'master:bobot-poin';

    /**
     * Poin periode yang berlaku pada $tanggal (baris dengan berlaku_mulai terbaru), atau null bila tidak ada.
     */
    public function bobot(string $jenisId, string $tingkatId, string $capaianId, CarbonInterface $tanggal): ?string
    {
        $hari = $tanggal->toDateString();
        $periode = $this->semua()["{$jenisId}:{$tingkatId}:{$capaianId}"] ?? [];

        $terpilih = collect($periode)
            ->filter(fn (array $p) => $p['mulai'] <= $hari && ($p['sampai'] === null || $p['sampai'] >= $hari))
            ->sortByDesc('mulai')
            ->first();

        return $terpilih['poin'] ?? null;
    }

    /** @return array<string, list<array{mulai: string, sampai: ?string, poin: string}>> */
    private function semua(): array
    {
        return Cache::rememberForever(self::KUNCI_CACHE, function (): array {
            $peta = [];
            foreach (BobotPoin::query()->get() as $b) {
                $peta["{$b->jenis_kegiatan_id}:{$b->tingkat_id}:{$b->capaian_id}"][] = [
                    'mulai' => $b->berlaku_mulai->toDateString(),
                    'sampai' => $b->berlaku_sampai?->toDateString(),
                    'poin' => (string) $b->poin,
                ];
            }

            return $peta;
        });
    }
}

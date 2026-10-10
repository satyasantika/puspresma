<?php

namespace App\Actions\Bukti;

use App\Enums\JenisTautan;
use App\Models\Prestasi;
use App\Models\TautanBerkas;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Menyelaraskan daftar tautan bukti dari formulir (tambah/ubah/hapus) lewat Action tunggal. */
class SinkronTautanBukti
{
    public function __construct(
        private readonly SimpanTautanBukti $simpan,
        private readonly UbahTautanBukti $ubah,
        private readonly HapusTautanBukti $hapus,
    ) {}

    /**
     * @param  list<array{id?: ?string, jenis?: ?string, url?: ?string, label?: ?string, tampil_publik?: ?bool}>  $items
     */
    public function jalankan(Prestasi $prestasi, array $items, User $pelaku): void
    {
        $items = array_values(array_filter($items, fn ($i) => filled($i['url'] ?? null)));

        // Tanpa perubahan: jangan menguji izin (mis. prestasi sudah terkunci dan formulir hanya dibaca).
        $sekarang = $prestasi->tautanBukti()->get()->keyBy('id');
        if ($this->tidakBerubah($sekarang->all(), $items)) {
            return;
        }

        Gate::forUser($pelaku)->authorize('kelolaBukti', $prestasi);

        DB::transaction(function () use ($prestasi, $items, $pelaku, $sekarang): void {
            $dipertahankan = [];
            foreach ($items as $item) {
                $publik = (bool) ($item['tampil_publik'] ?? false);
                if (filled($item['id'] ?? null) && $sekarang->has($item['id'])) {
                    $dipertahankan[] = $item['id'];
                    $this->ubah->jalankan($sekarang[$item['id']], (string) $item['url'], $item['label'] ?? null, $publik, $pelaku);

                    continue;
                }
                $this->simpan->jalankan($prestasi, JenisTautan::from((string) $item['jenis']), (string) $item['url'], $item['label'] ?? null, $publik, $pelaku);
            }

            $sekarang->each(function (TautanBerkas $tautan) use ($dipertahankan, $pelaku): void {
                if (! in_array($tautan->id, $dipertahankan, true)) {
                    $this->hapus->jalankan($tautan, $pelaku);
                }
            });
        });
    }

    /**
     * @param  array<string, TautanBerkas>  $sekarang
     * @param  list<array<string, mixed>>  $items
     */
    private function tidakBerubah(array $sekarang, array $items): bool
    {
        if (count($sekarang) !== count($items)) {
            return false;
        }

        foreach ($items as $item) {
            $ada = $sekarang[$item['id'] ?? ''] ?? null;
            if ($ada === null
                || trim((string) $item['url']) !== $ada->url
                || (filled($item['label'] ?? null) ? $item['label'] : null) !== $ada->label
                || (bool) ($item['tampil_publik'] ?? false) !== $ada->tampil_publik) {
                return false;
            }
        }

        return true;
    }
}

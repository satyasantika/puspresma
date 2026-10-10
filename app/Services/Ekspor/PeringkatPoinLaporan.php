<?php

namespace App\Services\Ekspor;

use App\Models\Mahasiswa;
use App\Models\User;
use App\Services\PeringkatPoin;
use Generator;
use Illuminate\Support\Collection;

/** LAP-06: peringkat poin mahasiswa per periode (dari PeringkatPoin F5.5). */
class PeringkatPoinLaporan extends LaporanEkspor
{
    public function jenis(): string
    {
        return 'peringkat_poin';
    }

    public function judul(): string
    {
        return 'Peringkat Poin Mahasiswa';
    }

    public function header(User $pengguna): array
    {
        return ['Peringkat', 'NPM', 'Nama', 'Prodi', 'Jumlah Prestasi', 'Total Poin'];
    }

    public function jumlah(array $filter, User $pengguna): int
    {
        return $this->data($filter, $pengguna)->count();
    }

    public function baris(array $filter, User $pengguna): Generator
    {
        foreach ($this->data($filter, $pengguna) as $b) {
            yield [$b['peringkat'], $b['mahasiswa']->npm, $b['mahasiswa']->nama, $b['mahasiswa']->prodi?->nama, $b['jumlah_prestasi'], $b['total_poin']];
        }
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return Collection<int, array{peringkat: int, mahasiswa: Mahasiswa, jumlah_prestasi: int, total_poin: string}>
     */
    private function data(array $filter, User $pengguna): Collection
    {
        return app(PeringkatPoin::class)->hitung($pengguna, [
            'dari' => $filter['dari'] ?? null, 'sampai' => $filter['sampai'] ?? null,
            'prodi_id' => $filter['prodi_id'] ?? null, 'angkatan' => $filter['angkatan'] ?? null,
        ], 10000);
    }
}

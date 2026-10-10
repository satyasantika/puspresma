<?php

namespace App\Services\Ekspor;

use App\Models\Kompetisi;
use App\Models\User;
use Generator;
use Illuminate\Database\Eloquent\Builder;

/** LAP-09: rekap katalog (hanya pengelola katalog fakultas). */
class RekapKatalogLaporan extends LaporanEkspor
{
    public function jenis(): string
    {
        return 'katalog';
    }

    public function judul(): string
    {
        return 'Rekap Katalog Kompetisi';
    }

    public function izin(): string
    {
        return 'kompetisi.kelola';
    }

    /** @return list<string> */
    public function format(): array
    {
        return ['xlsx'];
    }

    public function header(User $pengguna): array
    {
        return ['Judul', 'Penyelenggara', 'Status', 'Tanggal Terbit', 'Tenggat Pendaftaran', 'Jumlah Minat', 'Jumlah Prestasi Tertaut'];
    }

    public function jumlah(array $filter, User $pengguna): int
    {
        return $this->query($filter)->count();
    }

    public function baris(array $filter, User $pengguna): Generator
    {
        foreach ($this->query($filter)->reorder()->withCount(['minat', 'prestasi'])->lazyById((int) config('puspresma.ekspor.chunk', 500), 'id') as $k) {
            /** @var Kompetisi $k */
            yield [$k->judul, $k->penyelenggara, $k->status->label(), $k->diterbitkan_at?->toDateString(), $k->tenggat_pendaftaran->toDateTimeString(), $k->minat_count, $k->prestasi_count];
        }
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return Builder<Kompetisi>
     */
    private function query(array $filter): Builder
    {
        return Kompetisi::query()->when($filter['tahun'] ?? null, fn ($q, $v) => $q->whereYear('tenggat_pendaftaran', $v))->orderByDesc('tenggat_pendaftaran');
    }
}

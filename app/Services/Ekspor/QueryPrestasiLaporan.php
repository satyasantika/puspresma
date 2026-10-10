<?php

namespace App\Services\Ekspor;

use App\Models\Prestasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;

/** Kueri prestasi bersama untuk laporan: cakupan peran (BR-15), status (BR-24), dan filter. */
class QueryPrestasiLaporan
{
    /**
     * @param  array<string, mixed>  $filter  dari, sampai, tahun, prodi_id, jenis_kegiatan_id, bidang, tingkat_id, capaian_id, status
     * @return Builder<Prestasi>
     */
    public static function bangun(array $filter, User $pengguna): Builder
    {
        $status = $filter['status'] ?? 'terverifikasi';

        /** @var Builder<Prestasi> $query */
        $query = Prestasi::query();

        $query->when($status !== 'semua', fn (Builder $q) => $q->where('prestasi.status', $status));

        // admin-prodi hanya prestasi yang melibatkan prodinya; pengguna lain bebas (mereka sudah lolos izin laporan).
        if (! $pengguna->can('prestasi.lihat-semua')) {
            $pengguna->prodi_id === null ? $query->whereRaw('1 = 0') : $query->melibatkanProdi($pengguna->prodi_id);
        }

        return $query
            ->when($filter['prodi_id'] ?? null, fn (Builder $q, $v) => $q->melibatkanProdi($v))
            ->when($filter['tahun'] ?? null, fn (Builder $q, $v) => $q->where('prestasi.tahun', $v))
            ->when($filter['dari'] ?? null, fn (Builder $q, $v) => $q->where('prestasi.tanggal_selesai', '>=', $v))
            ->when($filter['sampai'] ?? null, fn (Builder $q, $v) => $q->where('prestasi.tanggal_selesai', '<=', $v))
            ->when($filter['jenis_kegiatan_id'] ?? null, fn (Builder $q, $v) => $q->where('prestasi.jenis_kegiatan_id', $v))
            ->when($filter['bidang'] ?? null, fn (Builder $q, $v) => $q->where('prestasi.bidang', $v))
            ->when($filter['tingkat_id'] ?? null, fn (Builder $q, $v) => $q->where('prestasi.tingkat_id', $v))
            ->when($filter['capaian_id'] ?? null, fn (Builder $q, $v) => $q->where('prestasi.capaian_id', $v))
            ->orderByDesc('prestasi.tanggal_selesai')->orderBy('prestasi.id');
    }

    /**
     * Iterasi aman memori untuk ekspor. Urutan kueri dasar (tanggal) dibuang karena lazyById menomori halaman dengan id;
     * mempertahankan urutan lain membuat baris terlewat di antara potongan.
     *
     * @param  Builder<Prestasi>  $query
     * @param  list<string>  $with
     * @return LazyCollection<int, Prestasi>
     */
    public static function iterasi(Builder $query, array $with): LazyCollection
    {
        return $query->reorder()->with($with)->lazyById((int) config('puspresma.ekspor.chunk', 500), 'prestasi.id', 'id');
    }
}

<?php

namespace App\Services\Ekspor;

use App\Models\Prestasi;
use App\Models\User;
use App\Services\PemetaanSimkatmawa;
use Generator;
use Illuminate\Database\Eloquent\Builder;

/** LAP-05: ekspor kolom terpetakan SIMKATMAWA dengan URL bukti dari snapshot verifikasi (hanya terverifikasi). */
class SimkatmawaLaporan extends LaporanEkspor
{
    public function __construct(private readonly PemetaanSimkatmawa $pemetaan) {}

    public function jenis(): string
    {
        return 'simkatmawa';
    }

    public function judul(): string
    {
        return 'Ekspor SIMKATMAWA';
    }

    public function izin(): string
    {
        return 'simkatmawa.ekspor';
    }

    public function format(): array
    {
        return ['xlsx', 'csv'];
    }

    public function header(User $pengguna): array
    {
        return $this->pemetaan->header();
    }

    public function jumlah(array $filter, User $pengguna): int
    {
        if (config('simkatmawa.satu_baris_per') === 'prestasi') {
            return $this->query($filter, $pengguna)->count();
        }

        return (int) $this->query($filter, $pengguna)->withCount('anggota')->get()->sum('anggota_count');
    }

    public function baris(array $filter, User $pengguna): Generator
    {
        $perPrestasi = config('simkatmawa.satu_baris_per') === 'prestasi';

        foreach ($this->query($filter, $pengguna)->with(['jenisKegiatan', 'kategoriLomba', 'tingkat', 'capaian', 'dosenPembimbing', 'anggota.mahasiswa', 'anggota.prodi', 'snapshotTautan', 'tautanBukti'])
            ->lazyById(300, 'prestasi.id', 'id') as $p) {
            /** @var Prestasi $p */
            $anggota = $perPrestasi ? $p->anggota->where('is_pengaju', true) : $p->anggota;
            foreach ($anggota as $a) {
                yield $this->pemetaan->baris($p, $a);
            }
        }
    }

    /**
     * Prestasi yang akan diekspor (juga dipakai pratinjau dan penandaan).
     *
     * @param  array<string, mixed>  $filter  tahun, jenis_kegiatan_id, belum_dilaporkan (bawaan ya)
     * @return Builder<Prestasi>
     */
    public function query(array $filter, User $pengguna): Builder
    {
        $query = QueryPrestasiLaporan::bangun(array_intersect_key($filter, array_flip(['tahun', 'jenis_kegiatan_id'])), $pengguna);

        if ($filter['belum_dilaporkan'] ?? true) {
            $query->whereNull('prestasi.dilaporkan_simkatmawa_at');
        }

        return $query;
    }
}

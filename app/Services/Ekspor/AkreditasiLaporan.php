<?php

namespace App\Services\Ekspor;

use App\Models\Prodi;
use App\Models\Tingkat;
use App\Models\User;
use App\Services\StatistikPrestasi;
use Generator;
use Illuminate\Validation\ValidationException;

/** LAP-03: statistik prestasi prodi untuk akreditasi (tingkat × bidang × TS-2, TS-1, TS) + lampiran daftar prestasi. */
class AkreditasiLaporan extends LaporanEkspor
{
    public function jenis(): string
    {
        return 'akreditasi';
    }

    public function judul(): string
    {
        return 'Statistik Prestasi Mahasiswa untuk Akreditasi';
    }

    public function header(User $pengguna): array
    {
        return ['Tingkat', 'Kelompok', 'TS-2', 'TS-1', 'TS', 'Jumlah'];
    }

    public function catatanKaki(): string
    {
        return 'Format menyesuaikan instrumen LAMDIK yang berlaku (perlu verifikasi).';
    }

    public function jumlah(array $filter, User $pengguna): int
    {
        $this->parameter($filter, $pengguna);

        return QueryPrestasiLaporan::bangun($this->saring($filter), $pengguna)->count() + 8;
    }

    public function baris(array $filter, User $pengguna): Generator
    {
        [$prodiId, $ts] = $this->parameter($filter, $pengguna);
        $matriks = StatistikPrestasi::untuk(null, $prodiId)->matriksAkreditasi($ts);

        foreach (Tingkat::orderBy('urutan')->get() as $tingkat) {
            foreach (['akademik' => 'Akademik', 'non_akademik' => 'Non-akademik'] as $bidang => $label) {
                $per = $matriks[$tingkat->kode][$bidang] ?? [];
                $angka = [$per[$ts - 2] ?? 0, $per[$ts - 1] ?? 0, $per[$ts] ?? 0];

                yield [$tingkat->nama, $label, ...$angka, array_sum($angka)];
            }
        }
    }

    public function lembar(array $filter, User $pengguna): array
    {
        [$prodiId, $ts] = $this->parameter($filter, $pengguna);
        $judulKolom = ['TS-2 ('.($ts - 2).')', 'TS-1 ('.($ts - 1).')', "TS ({$ts})"];

        return [
            ['nama' => 'Statistik', 'header' => ['Tingkat', 'Kelompok', ...$judulKolom, 'Jumlah'], 'baris' => $this->baris($filter, $pengguna)],
            ['nama' => 'Lampiran', 'header' => ['Kegiatan', 'Tingkat', 'Capaian', 'Tahun', 'Mahasiswa (NPM-Nama)'], 'baris' => $this->lampiran($filter, $pengguna, $prodiId, $ts)],
        ];
    }

    /** @param  array<string, mixed>  $filter */
    public function keteranganFilter(array $filter): string
    {
        $prodi = Prodi::find($filter['prodi_id'] ?? null);

        return 'Prodi: '.($prodi !== null ? $prodi->nama : '—').' · Tahun akhir (TS): '.($filter['tahun'] ?? now()->year);
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array{0: string, 1: int} prodi id dan TS
     */
    private function parameter(array $filter, User $pengguna): array
    {
        $prodiId = $pengguna->can('prestasi.lihat-semua') ? ($filter['prodi_id'] ?? null) : $pengguna->prodi_id;

        if (blank($prodiId)) {
            throw ValidationException::withMessages(['prodi_id' => 'Pilih prodi untuk laporan akreditasi.']);
        }

        return [(string) $prodiId, (int) ($filter['tahun'] ?? now()->year)];
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array<string, mixed>
     */
    private function saring(array $filter): array
    {
        $ts = (int) ($filter['tahun'] ?? now()->year);

        return ['prodi_id' => $filter['prodi_id'] ?? null, 'dari' => ($ts - 2).'-01-01', 'sampai' => $ts.'-12-31'];
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return Generator<int, list<string|int|float|null>>
     */
    private function lampiran(array $filter, User $pengguna, string $prodiId, int $ts): Generator
    {
        $saring = ['prodi_id' => $prodiId, 'dari' => ($ts - 2).'-01-01', 'sampai' => $ts.'-12-31'];

        foreach (QueryPrestasiLaporan::iterasi(QueryPrestasiLaporan::bangun($saring, $pengguna), ['tingkat', 'capaian', 'anggota.mahasiswa']) as $p) {
            yield [
                $p->nama_kegiatan, $p->tingkat->nama, $p->capaian->nama, $p->tahun,
                $p->anggota->where('prodi_id', $prodiId)->map(fn ($a) => "{$a->mahasiswa->npm}-{$a->mahasiswa->nama}")->implode('; '),
            ];
        }
    }
}

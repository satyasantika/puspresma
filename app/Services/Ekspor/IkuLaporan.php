<?php

namespace App\Services\Ekspor;

use App\Models\Tingkat;
use App\Models\User;
use App\Services\Pengaturan;
use App\Services\StatistikPrestasi;
use Generator;
use Illuminate\Support\Facades\DB;

/** LAP-04: rekap IKU — mahasiswa UNIK FKIP yang memenuhi tingkat minimal (dan juara), per prodi dan daftar pemenuh. */
class IkuLaporan extends LaporanEkspor
{
    public function jenis(): string
    {
        return 'iku';
    }

    public function judul(): string
    {
        return 'Rekap IKU Mahasiswa Berprestasi';
    }

    public function header(User $pengguna): array
    {
        return ['Prodi', 'Jumlah Mahasiswa Unik'];
    }

    public function catatanKaki(): string
    {
        return 'Definisi IKU perlu verifikasi; parameter default diatur di Pengaturan Sistem.';
    }

    public function jumlah(array $filter, User $pengguna): int
    {
        return (int) $this->statistik($filter)['total'];
    }

    public function baris(array $filter, User $pengguna): Generator
    {
        $s = $this->statistik($filter);
        foreach ($s['per_prodi'] as $prodi => $n) {
            yield [$prodi, $n];
        }
        yield ['TOTAL FKIP (mahasiswa unik)', $s['total']];
    }

    public function lembar(array $filter, User $pengguna): array
    {
        return [
            ['nama' => 'Ringkasan', 'header' => $this->header($pengguna), 'baris' => $this->baris($filter, $pengguna)],
            ['nama' => 'Daftar Mahasiswa', 'header' => ['NPM', 'Nama', 'Prodi', 'Kegiatan', 'Tingkat', 'Capaian', 'Tahun'], 'baris' => $this->daftar($filter, $pengguna)],
        ];
    }

    /** @param  array<string, mixed>  $filter */
    public function keteranganFilter(array $filter): string
    {
        [$tahun, , $hanyaJuara, $kodeTingkat] = $this->parameter($filter);

        return "Tahun: {$tahun} · Tingkat minimal: {$kodeTingkat} · ".($hanyaJuara ? 'hanya juara' : 'semua capaian');
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array{0: int, 1: int, 2: bool, 3: string} tahun, urutan tingkat minimal, hanya juara, kode tingkat
     */
    private function parameter(array $filter): array
    {
        $kode = (string) ($filter['tingkat_minimal'] ?? Pengaturan::ambil('iku_tingkat_minimal', 'nasional'));
        $urutan = (int) (Tingkat::where('kode', $kode)->value('urutan') ?? 3);
        $hanyaJuara = array_key_exists('hanya_juara', $filter) ? (bool) $filter['hanya_juara'] : (bool) Pengaturan::ambil('iku_hanya_juara', true);

        return [(int) ($filter['tahun'] ?? now()->year), $urutan, $hanyaJuara, $kode];
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array{total: int, per_prodi: array<string, int>}
     */
    private function statistik(array $filter): array
    {
        [$tahun, $urutan, $hanyaJuara] = $this->parameter($filter);

        return StatistikPrestasi::untuk()->ikuMahasiswa($tahun, $urutan, $hanyaJuara, $filter['jenis_kegiatan_id'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return Generator<int, list<string|int|float|null>>
     */
    private function daftar(array $filter, User $pengguna): Generator
    {
        [$tahun, $urutan, $hanyaJuara] = $this->parameter($filter);

        $query = DB::table('prestasi_anggota')
            ->join('prestasi', 'prestasi.id', '=', 'prestasi_anggota.prestasi_id')
            ->join('tingkat', 'tingkat.id', '=', 'prestasi.tingkat_id')
            ->join('capaian', 'capaian.id', '=', 'prestasi.capaian_id')
            ->join('mahasiswa', 'mahasiswa.id', '=', 'prestasi_anggota.mahasiswa_id')
            ->join('prodi', 'prodi.id', '=', 'prestasi_anggota.prodi_id')
            ->where('prestasi.status', 'terverifikasi')->whereNull('prestasi.deleted_at')->where('prestasi.tahun', $tahun)
            ->where('tingkat.urutan', '>=', $urutan)
            ->when($hanyaJuara, fn ($q) => $q->where('capaian.is_juara', true))
            ->when($filter['jenis_kegiatan_id'] ?? null, fn ($q, $v) => $q->where('prestasi.jenis_kegiatan_id', $v))
            ->when(! $pengguna->can('prestasi.lihat-semua'), fn ($q) => $q->where('prestasi_anggota.prodi_id', $pengguna->prodi_id))
            ->orderBy('prodi.nama')->orderBy('mahasiswa.nama')
            ->select(['mahasiswa.npm', 'mahasiswa.nama', 'prodi.nama as prodi', 'prestasi.nama_kegiatan', 'tingkat.nama as tingkat', 'capaian.nama as capaian', 'prestasi.tahun']);

        foreach ($query->cursor() as $b) {
            yield [$b->npm, $b->nama, $b->prodi, $b->nama_kegiatan, $b->tingkat, $b->capaian, $b->tahun];
        }
    }
}

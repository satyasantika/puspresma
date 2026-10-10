<?php

namespace App\Services;

use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Statistik prestasi terverifikasi (BR-02, BR-21): satu kueri agregat per method, tanpa memuat model,
 * di-cache per versi dengan Cache::flexible. Statistik per prodi mengikuti prodi ANGGOTA; total fakultas menghitung prestasi unik.
 */
class StatistikPrestasi
{
    public function __construct(private readonly ?int $tahun = null, private readonly ?string $prodiId = null) {}

    public static function untuk(?int $tahun = null, ?string $prodiId = null): self
    {
        return new self($tahun, $prodiId);
    }

    /** @return array{total_prestasi: int, total_mahasiswa: int, internasional: int, nasional: int, juara: int, persen_juara: float} */
    public function ringkasan(): array
    {
        return $this->cache('ringkasan', function (): array {
            $baris = $this->dasar()
                ->join('tingkat', 'tingkat.id', '=', 'prestasi.tingkat_id')
                ->join('capaian', 'capaian.id', '=', 'prestasi.capaian_id')
                ->selectRaw("COUNT(*) as total, SUM(tingkat.kode = 'internasional') as internasional, SUM(tingkat.kode = 'nasional') as nasional, SUM(capaian.is_juara = 1) as juara")
                ->first();
            $total = (int) ($baris->total ?? 0);

            return [
                'total_prestasi' => $total,
                'total_mahasiswa' => $this->mahasiswaUnik(),
                'internasional' => (int) ($baris->internasional ?? 0),
                'nasional' => (int) ($baris->nasional ?? 0),
                'juara' => (int) ($baris->juara ?? 0),
                'persen_juara' => $total === 0 ? 0.0 : round(((int) $baris->juara / $total) * 100, 1),
            ];
        });
    }

    /**
     * Jumlah prestasi per prodi anggota (COUNT DISTINCT prestasi).
     *
     * @return array<string, int> nama prodi => jumlah
     */
    public function perProdi(): array
    {
        return $this->cache('per_prodi', fn (): array => DB::table('prestasi_anggota')
            ->join('prestasi', 'prestasi.id', '=', 'prestasi_anggota.prestasi_id')
            ->join('prodi', 'prodi.id', '=', 'prestasi_anggota.prodi_id')
            ->where('prestasi.status', 'terverifikasi')->whereNull('prestasi.deleted_at')
            ->when($this->tahun, fn (Builder $q) => $q->where('prestasi.tahun', $this->tahun))
            ->when($this->prodiId, fn (Builder $q) => $q->where('prestasi_anggota.prodi_id', $this->prodiId))
            ->groupBy('prodi.id', 'prodi.nama')->orderBy('prodi.nama')
            ->selectRaw('prodi.nama as label, COUNT(DISTINCT prestasi_anggota.prestasi_id) as jumlah')
            ->pluck('jumlah', 'label')->map(fn ($n) => (int) $n)->all());
    }

    /** @return array<string, int> */
    public function perTingkat(): array
    {
        return $this->kelompok('per_tingkat', 'tingkat', 'tingkat.id', 'prestasi.tingkat_id', 'tingkat.urutan');
    }

    /** @return array<string, int> */
    public function perJenis(): array
    {
        return $this->kelompok('per_jenis', 'jenis_kegiatan', 'jenis_kegiatan.id', 'prestasi.jenis_kegiatan_id', 'jenis_kegiatan.urutan');
    }

    /** @return array<string, int> */
    public function perCapaian(): array
    {
        return $this->kelompok('per_capaian', 'capaian', 'capaian.id', 'prestasi.capaian_id', 'capaian.urutan');
    }

    /** @return array<string, int> */
    public function perBidang(): array
    {
        return $this->cache('per_bidang', fn (): array => $this->dasar()->groupBy('prestasi.bidang')->orderBy('prestasi.bidang')
            ->selectRaw('prestasi.bidang as label, COUNT(*) as jumlah')->pluck('jumlah', 'label')->map(fn ($n) => (int) $n)->all());
    }

    /**
     * Jumlah per bulan (Jan–Des) pada tahun terpilih atau tahun berjalan.
     *
     * @return array<int, int> bulan => jumlah
     */
    public function perBulan(): array
    {
        $tahun = $this->tahun ?? (int) now()->year;

        return $this->cache('per_bulan', function () use ($tahun): array {
            $hasil = DB::table('prestasi')->where('status', 'terverifikasi')->whereNull('deleted_at')->where('tahun', $tahun)
                ->when($this->prodiId, fn (Builder $q) => $q->whereExists($this->anggotaProdi()))
                ->selectRaw('MONTH(tanggal_selesai) as bulan, COUNT(*) as jumlah')->groupBy('bulan')->pluck('jumlah', 'bulan');

            return collect(range(1, 12))->mapWithKeys(fn ($b) => [$b => (int) ($hasil[$b] ?? 0)])->all();
        });
    }

    /**
     * Lima tahun terakhir hingga tahun terpilih/berjalan.
     *
     * @return array<int, int> tahun => jumlah
     */
    public function trenTahunan(): array
    {
        $akhir = $this->tahun ?? (int) now()->year;

        return $this->cache('tren_tahunan', function () use ($akhir): array {
            $hasil = DB::table('prestasi')->where('status', 'terverifikasi')->whereNull('deleted_at')->whereBetween('tahun', [$akhir - 4, $akhir])
                ->when($this->prodiId, fn (Builder $q) => $q->whereExists($this->anggotaProdi()))
                ->selectRaw('tahun, COUNT(*) as jumlah')->groupBy('tahun')->pluck('jumlah', 'tahun');

            return collect(range($akhir - 4, $akhir))->mapWithKeys(fn ($t) => [$t => (int) ($hasil[$t] ?? 0)])->all();
        });
    }

    /**
     * Matriks akreditasi (LAP-03): jumlah prestasi per tingkat × bidang × tahun (TS-2..TS) untuk prodi terpilih.
     *
     * @return array<string, array<string, array<int, int>>> kode tingkat => bidang => tahun => jumlah
     */
    public function matriksAkreditasi(int $tahunAkhir): array
    {
        return $this->cache("akreditasi:{$tahunAkhir}", function () use ($tahunAkhir): array {
            $hasil = [];
            DB::table('prestasi')->join('tingkat', 'tingkat.id', '=', 'prestasi.tingkat_id')
                ->where('prestasi.status', 'terverifikasi')->whereNull('prestasi.deleted_at')
                ->whereBetween('prestasi.tahun', [$tahunAkhir - 2, $tahunAkhir])
                ->when($this->prodiId, fn (Builder $q) => $q->whereExists($this->anggotaProdi()))
                ->selectRaw('tingkat.kode as tingkat, prestasi.bidang, prestasi.tahun, COUNT(*) as jumlah')
                ->groupBy('tingkat.kode', 'prestasi.bidang', 'prestasi.tahun')->get()
                ->each(function (object $b) use (&$hasil): void {
                    $hasil[$b->tingkat][$b->bidang][(int) $b->tahun] = (int) $b->jumlah;
                });

            return $hasil;
        });
    }

    /**
     * Mahasiswa UNIK yang memenuhi IKU (LAP-04): total fakultas dan per prodi anggota.
     *
     * @return array{total: int, per_prodi: array<string, int>}
     */
    public function ikuMahasiswa(int $tahun, int $urutanTingkatMinimal, bool $hanyaJuara, ?string $jenisId = null): array
    {
        return $this->cache("iku:{$tahun}:{$urutanTingkatMinimal}:".(int) $hanyaJuara.':'.($jenisId ?? 'semua'), function () use ($tahun, $urutanTingkatMinimal, $hanyaJuara, $jenisId): array {
            $dasar = fn () => DB::table('prestasi_anggota')
                ->join('prestasi', 'prestasi.id', '=', 'prestasi_anggota.prestasi_id')
                ->join('tingkat', 'tingkat.id', '=', 'prestasi.tingkat_id')
                ->join('capaian', 'capaian.id', '=', 'prestasi.capaian_id')
                ->where('prestasi.status', 'terverifikasi')->whereNull('prestasi.deleted_at')->where('prestasi.tahun', $tahun)
                ->where('tingkat.urutan', '>=', $urutanTingkatMinimal)
                ->when($hanyaJuara, fn (Builder $q) => $q->where('capaian.is_juara', true))
                ->when($jenisId, fn (Builder $q) => $q->where('prestasi.jenis_kegiatan_id', $jenisId));

            return [
                'total' => (int) $dasar()->distinct()->count('prestasi_anggota.mahasiswa_id'),
                'per_prodi' => $dasar()->join('prodi', 'prodi.id', '=', 'prestasi_anggota.prodi_id')
                    ->groupBy('prodi.id', 'prodi.nama')->orderBy('prodi.nama')
                    ->selectRaw('prodi.nama as label, COUNT(DISTINCT prestasi_anggota.mahasiswa_id) as jumlah')
                    ->pluck('jumlah', 'label')->map(fn ($n) => (int) $n)->all(),
            ];
        });
    }

    /** Mahasiswa unik berprestasi (kredit penuh setiap anggota). */
    private function mahasiswaUnik(): int
    {
        return (int) DB::table('prestasi_anggota')
            ->join('prestasi', 'prestasi.id', '=', 'prestasi_anggota.prestasi_id')
            ->where('prestasi.status', 'terverifikasi')->whereNull('prestasi.deleted_at')
            ->when($this->tahun, fn (Builder $q) => $q->where('prestasi.tahun', $this->tahun))
            ->when($this->prodiId, fn (Builder $q) => $q->where('prestasi_anggota.prodi_id', $this->prodiId))
            ->distinct()->count('prestasi_anggota.mahasiswa_id');
    }

    /** @return array<string, int> */
    private function kelompok(string $nama, string $tabel, string $kunciTabel, string $kolom, string $urut): array
    {
        return $this->cache($nama, fn (): array => $this->dasar()->join($tabel, $kunciTabel, '=', $kolom)
            ->groupBy("{$tabel}.id", "{$tabel}.nama", $urut)->orderBy($urut)->orderBy("{$tabel}.nama")
            ->selectRaw("{$tabel}.nama as label, COUNT(*) as jumlah")->pluck('jumlah', 'label')->map(fn ($n) => (int) $n)->all());
    }

    /** Prestasi terverifikasi (unik), disaring tahun dan prodi anggota. */
    private function dasar(): Builder
    {
        return DB::table('prestasi')->where('prestasi.status', 'terverifikasi')->whereNull('prestasi.deleted_at')
            ->when($this->tahun, fn (Builder $q) => $q->where('prestasi.tahun', $this->tahun))
            ->when($this->prodiId, fn (Builder $q) => $q->whereExists($this->anggotaProdi()));
    }

    private function anggotaProdi(): Closure
    {
        return fn (Builder $q) => $q->selectRaw('1')->from('prestasi_anggota')
            ->whereColumn('prestasi_anggota.prestasi_id', 'prestasi.id')
            ->where('prestasi_anggota.prodi_id', $this->prodiId);
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $hitung
     * @return T
     */
    private function cache(string $metode, Closure $hitung): mixed
    {
        $kunci = 'statistik:v'.(int) Cache::get('statistik:versi', 0).':'.($this->tahun ?? 'semua').':'.($this->prodiId ?? 'semua').':'.$metode;

        return Cache::flexible($kunci, [600, 1800], $hitung);
    }
}

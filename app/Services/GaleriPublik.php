<?php

namespace App\Services;

use App\Contracts\PenyimpananBerkas;
use App\Enums\JenisTautan;
use App\Enums\PenyediaTautan;
use App\Enums\StatusCekTautan;
use App\Models\Prestasi;
use App\Models\TautanBerkas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Data galeri publik sebagai array (DTO) yang di-cache per versi (10 menit). Hanya data publik:
 * tanpa NPM, surel, tautan sertifikat/surat tugas, atau URL Drive selain URL gambar lh3 foto yang disetujui (BR-16).
 */
class GaleriPublik
{
    public const PER_HALAMAN = 12;

    public function __construct(private readonly PenyimpananBerkas $penyimpanan) {}

    public static function aktif(): bool
    {
        return (bool) Pengaturan::ambil('galeri_aktif', true);
    }

    /**
     * @param  array{tahun?: ?int, tingkat?: ?string, prodi?: ?string, jenis?: ?string}  $filter
     * @return array{items: list<array<string, mixed>>, total: int, halaman: int, terakhir: int}
     */
    public function daftar(array $filter = [], int $halaman = 1): array
    {
        $halaman = max(1, $halaman);
        $kunci = 'galeri:v'.self::versi().':daftar:'.md5(json_encode($filter)).':'.$halaman;

        return Cache::remember($kunci, now()->addMinutes(10), function () use ($filter, $halaman): array {
            $query = $this->query($filter);
            $total = (clone $query)->count();
            $prestasi = $query->forPage($halaman, self::PER_HALAMAN)->get();

            return [
                'items' => $this->dtoKumpulan($prestasi, ambilSemuaFoto: false),
                'total' => $total,
                'halaman' => $halaman,
                'terakhir' => max(1, (int) ceil($total / self::PER_HALAMAN)),
            ];
        });
    }

    /** @return list<array<string, mixed>> */
    public function terbaru(int $jumlah = 6): array
    {
        if (! self::aktif()) {
            return [];
        }

        return Cache::remember('galeri:v'.self::versi().":daftar:terbaru:{$jumlah}", now()->addMinutes(10),
            fn () => $this->dtoKumpulan($this->query([])->limit($jumlah)->get(), ambilSemuaFoto: false));
    }

    /**
     * Detail satu prestasi; null bila tidak boleh tampil.
     *
     * @return array<string, mixed>|null
     */
    public function detail(Prestasi $prestasi): ?array
    {
        if (! self::aktif()) {
            return null;
        }

        return Cache::remember('galeri:v'.self::versi().":item:{$prestasi->getKey()}", now()->addMinutes(10), function () use ($prestasi): ?array {
            $ada = $this->query([])->whereKey($prestasi->getKey())->get();

            return $ada->isEmpty() ? null : $this->dtoKumpulan($ada, ambilSemuaFoto: true)[0];
        });
    }

    private static function versi(): int
    {
        return (int) Cache::get('galeri:versi', 0);
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return Builder<Prestasi>
     */
    private function query(array $filter): Builder
    {
        return Prestasi::query()->tampilDiGaleri()
            ->with(['tingkat', 'capaian', 'jenisKegiatan', 'anggota.mahasiswa', 'anggota.prodi', 'anggotaEksternal'])
            ->when($filter['tahun'] ?? null, fn (Builder $q, $v) => $q->where('tahun', $v))
            ->when($filter['tingkat'] ?? null, fn (Builder $q, $v) => $q->where('tingkat_id', $v))
            ->when($filter['jenis'] ?? null, fn (Builder $q, $v) => $q->where('jenis_kegiatan_id', $v))
            ->when($filter['prodi'] ?? null, fn (Builder $q, $v) => $q->whereHas('anggota', fn (Builder $a) => $a->where('prodi_id', $v)))
            ->orderByDesc('tanggal_selesai')->orderByDesc('id');
    }

    /**
     * @param  Collection<int, Prestasi>  $kumpulan
     * @return list<array<string, mixed>>
     */
    private function dtoKumpulan(Collection $kumpulan, bool $ambilSemuaFoto): array
    {
        // Satu kueri foto untuk seluruh halaman (tanpa N+1): hanya foto yang memenuhi syarat BR-16.
        $foto = TautanBerkas::query()
            ->where('pemilik_type', (new Prestasi)->getMorphClass())
            ->whereIn('pemilik_id', $kumpulan->pluck('id'))
            ->where('jenis', JenisTautan::FotoPenyerahan->value)->where('tampil_publik', true)
            ->where('penyedia', PenyediaTautan::GoogleDrive->value)->whereNotNull('drive_file_id')
            ->where('status_cek', StatusCekTautan::DapatDiakses->value)
            ->orderBy('created_at')->get()->groupBy('pemilik_id');

        return $kumpulan->map(function (Prestasi $p) use ($foto, $ambilSemuaFoto): array {
            $setuju = $p->anggota->where('setuju_publikasi', true);
            $totalAnggota = $p->anggota->count() + $p->anggotaEksternal->count();
            $fotoPrestasi = ($foto[$p->id] ?? collect())->map(fn (TautanBerkas $t) => [
                'url' => $this->penyimpanan->urlGambar($t),
                'alt' => $t->label ?: 'Foto penyerahan '.$p->nama_kegiatan,
            ])->values();

            return [
                'id' => $p->id,
                'judul' => $p->nama_kegiatan,
                'judul_karya' => $ambilSemuaFoto ? $p->judul_karya : null,
                'deskripsi' => $ambilSemuaFoto ? $p->deskripsi : null,
                'capaian' => $p->capaian->nama,
                'capaian_keterangan' => $p->capaian_keterangan,
                'tingkat' => $p->tingkat->nama,
                'jenis' => $p->jenisKegiatan->nama,
                'penyelenggara' => $p->penyelenggara,
                'tahun' => $p->tahun,
                'tanggal_selesai' => $p->tanggal_selesai->toDateString(),
                'anggota' => $setuju->map(fn ($a) => ['nama' => $a->mahasiswa->nama, 'prodi' => $a->prodi->nama])->values()->all(),
                'anggota_lain' => $totalAnggota - $setuju->count(),
                'foto' => ($ambilSemuaFoto ? $fotoPrestasi : $fotoPrestasi->take(1))->all(),
            ];
        })->all();
    }
}

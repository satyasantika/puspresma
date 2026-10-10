<?php

namespace App\Services;

use App\Models\Kompetisi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/** Data kompetisi untuk halaman publik/portal sebagai array (DTO) yang di-cache per versi katalog (10 menit). */
class KatalogPublik
{
    public const PER_HALAMAN = 12;

    /**
     * @param  array{q?: ?string, jenis?: ?string, tingkat?: ?string, kategori?: ?string, prodi?: ?string}  $filter
     * @return array{items: list<array<string, mixed>>, total: int, halaman: int, terakhir: int}
     */
    public function daftar(array $filter = [], int $halaman = 1): array
    {
        $halaman = max(1, $halaman);
        $kunci = 'katalog:v'.(int) Cache::get('katalog:versi', 0).':aktif:'.md5(json_encode($filter).'|'.$halaman);

        return Cache::remember($kunci, now()->addMinutes(10), function () use ($filter, $halaman): array {
            $query = $this->query($filter);
            $total = (clone $query)->count();
            $items = $query->forPage($halaman, self::PER_HALAMAN)->get()->map(fn (Kompetisi $k) => $this->dto($k))->all();

            return ['items' => $items, 'total' => $total, 'halaman' => $halaman, 'terakhir' => max(1, (int) ceil($total / self::PER_HALAMAN))];
        });
    }

    /**
     * Kompetisi dengan tenggat terdekat (beranda).
     *
     * @return list<array<string, mixed>>
     */
    public function terdekat(int $jumlah = 4): array
    {
        return Cache::remember('katalog:v'.(int) Cache::get('katalog:versi', 0).":terdekat:{$jumlah}", now()->addMinutes(10),
            fn () => $this->query([])->limit($jumlah)->get()->map(fn (Kompetisi $k) => $this->dto($k))->all());
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return Builder<Kompetisi>
     */
    private function query(array $filter): Builder
    {
        return Kompetisi::query()->masihDibuka()
            ->with(['tingkat', 'jenisKegiatan', 'kategoriLomba', 'poster', 'prodiSasaran'])
            ->when($filter['jenis'] ?? null, fn (Builder $q, $v) => $q->where('jenis_kegiatan_id', $v))
            ->when($filter['tingkat'] ?? null, fn (Builder $q, $v) => $q->where('tingkat_id', $v))
            ->when($filter['kategori'] ?? null, fn (Builder $q, $v) => $q->where('kategori_lomba_id', $v))
            ->when($filter['prodi'] ?? null, fn (Builder $q, $v) => $q->where(fn (Builder $w) => $w->whereDoesntHave('prodiSasaran')->orWhereHas('prodiSasaran', fn ($p) => $p->where('prodi.id', $v))))
            ->when(filled($filter['q'] ?? null), fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereFullText(['judul', 'penyelenggara', 'deskripsi'], $filter['q'])
                ->orWhere('judul', 'like', '%'.$filter['q'].'%')
                ->orWhere('penyelenggara', 'like', '%'.$filter['q'].'%')))
            ->orderBy('tenggat_pendaftaran');
    }

    /** @return array<string, mixed> */
    private function dto(Kompetisi $k): array
    {
        return [
            'id' => $k->id,
            'slug' => $k->slug,
            'judul' => $k->judul,
            'penyelenggara' => $k->penyelenggara,
            'jenis' => $k->jenisKegiatan->nama,
            'tingkat' => $k->tingkat?->nama,
            'kategori' => $k->kategoriLomba?->nama,
            'tenggat' => $k->tenggat_pendaftaran->toIso8601String(),
            'prodi_sasaran' => $k->prodiSasaran->pluck('id')->all(),
            'poster_id' => $k->poster?->drive_file_id,
        ];
    }
}

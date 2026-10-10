<?php

namespace App\Services;

use App\Models\Capaian;
use App\Models\JenisKegiatan;
use App\Models\KategoriLomba;
use App\Models\Tingkat;
use Illuminate\Support\Facades\Cache;

/** Opsi master (id => nama, hanya aktif) dari cache; dibuang oleh MasterObserver. */
class OpsiMaster
{
    /** @return array<string, string> */
    public function jenisKegiatan(): array
    {
        return Cache::rememberForever('master:jenis-kegiatan',
            fn () => JenisKegiatan::query()->aktif()->terurut()->pluck('nama', 'id')->all());
    }

    /**
     * Kategori lomba aktif; bila $jenisId diisi: kategori jenis itu ditambah kategori "semua jenis".
     *
     * @return array<string, string>
     */
    public function kategoriLomba(?string $jenisId = null): array
    {
        /** @var list<array{id: string, jenis_kegiatan_id: ?string, nama: string}> $semua */
        $semua = Cache::rememberForever('master:kategori-lomba',
            fn () => KategoriLomba::query()->aktif()->terurut()
                ->get(['id', 'jenis_kegiatan_id', 'nama'])->toArray());

        $hasil = [];
        foreach ($semua as $baris) {
            if ($jenisId === null || $baris['jenis_kegiatan_id'] === null || $baris['jenis_kegiatan_id'] === $jenisId) {
                $hasil[$baris['id']] = $baris['nama'];
            }
        }

        return $hasil;
    }

    /** @return array<string, string> */
    public function tingkat(): array
    {
        return Cache::rememberForever('master:tingkat',
            fn () => Tingkat::query()->aktif()->terurut()->pluck('nama', 'id')->all());
    }

    /** @return array<string, string> */
    public function capaian(string $jenisId): array
    {
        return Cache::rememberForever("master:capaian:{$jenisId}",
            fn () => Capaian::query()->aktif()->where('jenis_kegiatan_id', $jenisId)->terurut()->pluck('nama', 'id')->all());
    }

    /** Kode tingkat berdasarkan id (mis. nasional/internasional) untuk validasi kondisional. */
    public function kodeTingkat(string $tingkatId): ?string
    {
        return Cache::rememberForever('master:tingkat-kode',
            fn () => Tingkat::query()->pluck('kode', 'id')->all())[$tingkatId] ?? null;
    }
}

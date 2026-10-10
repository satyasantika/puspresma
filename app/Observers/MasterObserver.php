<?php

namespace App\Observers;

use App\Models\Capaian;
use App\Models\JenisKegiatan;
use App\Models\KategoriLomba;
use App\Models\Tingkat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Membuang cache OpsiMaster setiap kali data master berubah. */
class MasterObserver
{
    public function saved(Model $model): void
    {
        $this->buang($model);
    }

    public function deleted(Model $model): void
    {
        $this->buang($model);
    }

    public function restored(Model $model): void
    {
        $this->buang($model);
    }

    private function buang(Model $model): void
    {
        match (true) {
            $model instanceof JenisKegiatan => $this->buangJenisKegiatan($model),
            $model instanceof KategoriLomba => Cache::forget('master:kategori-lomba'),
            $model instanceof Tingkat => $this->buangTingkat(),
            $model instanceof Capaian => $this->buangCapaian($model),
            default => null,
        };
    }

    private function buangTingkat(): void
    {
        Cache::forget('master:tingkat');
        Cache::forget('master:tingkat-kode');
    }

    private function buangJenisKegiatan(JenisKegiatan $jenis): void
    {
        Cache::forget('master:jenis-kegiatan');
        Cache::forget("master:capaian:{$jenis->getKey()}");
    }

    private function buangCapaian(Capaian $capaian): void
    {
        Cache::forget("master:capaian:{$capaian->jenis_kegiatan_id}");

        $lama = $capaian->getOriginal('jenis_kegiatan_id');
        if ($lama !== null) {
            Cache::forget("master:capaian:{$lama}");
        }
    }
}

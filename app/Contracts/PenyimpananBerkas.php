<?php

namespace App\Contracts;

use App\Enums\JenisTautan;
use App\Models\TautanBerkas;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Satu-satunya pintu akses berkas; kelak dapat diganti implementasi disk/S3 tanpa mengubah modul. */
interface PenyimpananBerkas
{
    /** Memvalidasi, menormalkan, dan menyimpan tautan; pemeriksaan keteraksesan berjalan di antrean. */
    public function simpanTautan(Model $pemilik, JenisTautan $jenis, string $url, ?string $label, User $pengguna): TautanBerkas;

    public function urlBuka(TautanBerkas $tautan): string;

    /** URL sematan (iframe) bila penyedia mendukung; null bila tidak. */
    public function urlPratinjau(TautanBerkas $tautan): ?string;

    /** URL gambar langsung (untuk foto) bila penyedia mendukung; null bila tidak. */
    public function urlGambar(TautanBerkas $tautan): ?string;
}

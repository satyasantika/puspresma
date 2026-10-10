<?php

namespace App\Models;

use App\Models\Concerns\TercatatAktivitas;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Anggota dari luar FKIP: dicatat tetapi tidak dihitung dalam statistik (BR-02). */
#[Fillable(['prestasi_id', 'nama', 'nomor_induk', 'institusi', 'program_studi'])]
class PrestasiAnggotaEksternal extends Model
{
    use HasUuids, TercatatAktivitas;

    protected $table = 'prestasi_anggota_eksternal';

    protected function namaLog(): string
    {
        return 'prestasi-anggota';
    }

    protected function atributTercatat(): array
    {
        return ['prestasi_id', 'nama', 'nomor_induk', 'institusi', 'program_studi'];
    }

    /** @return BelongsTo<Prestasi, $this> */
    public function prestasi(): BelongsTo
    {
        return $this->belongsTo(Prestasi::class);
    }
}

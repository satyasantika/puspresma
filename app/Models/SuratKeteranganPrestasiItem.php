<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['surat_keterangan_prestasi_id', 'prestasi_id'])]
class SuratKeteranganPrestasiItem extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'surat_keterangan_prestasi_item';

    /** @return BelongsTo<SuratKeteranganPrestasi, $this> */
    public function surat(): BelongsTo
    {
        return $this->belongsTo(SuratKeteranganPrestasi::class, 'surat_keterangan_prestasi_id');
    }

    /** @return BelongsTo<Prestasi, $this> */
    public function prestasi(): BelongsTo
    {
        return $this->belongsTo(Prestasi::class);
    }
}

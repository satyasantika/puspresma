<?php

namespace App\Models;

use App\Models\Concerns\ScopeMaster;
use App\Models\Concerns\TercatatAktivitas;
use App\Observers\MasterObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['jenis_kegiatan_id', 'nama', 'kode_simkatmawa', 'is_active'])]
#[ObservedBy(MasterObserver::class)]
class KategoriLomba extends Model
{
    use HasUuids, ScopeMaster, SoftDeletes, TercatatAktivitas;

    protected $table = 'kategori_lomba';

    protected $attributes = ['is_active' => true];

    protected function atributTercatat(): array
    {
        return ['jenis_kegiatan_id', 'nama', 'kode_simkatmawa', 'is_active'];
    }

    /** @return BelongsTo<JenisKegiatan, $this> */
    public function jenisKegiatan(): BelongsTo
    {
        return $this->belongsTo(JenisKegiatan::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}

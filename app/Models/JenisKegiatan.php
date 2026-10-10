<?php

namespace App\Models;

use App\Models\Concerns\ScopeMaster;
use App\Models\Concerns\TercatatAktivitas;
use App\Observers\MasterObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['kode', 'nama', 'deskripsi', 'kode_simkatmawa', 'wajib_penyelenggara', 'urutan', 'is_active'])]
#[ObservedBy(MasterObserver::class)]
class JenisKegiatan extends Model
{
    use HasUuids, ScopeMaster, SoftDeletes, TercatatAktivitas;

    protected $table = 'jenis_kegiatan';

    protected $attributes = ['wajib_penyelenggara' => true, 'urutan' => 0, 'is_active' => true];

    protected function atributTercatat(): array
    {
        return ['kode', 'nama', 'kode_simkatmawa', 'wajib_penyelenggara', 'urutan', 'is_active'];
    }

    /** @return HasMany<KategoriLomba, $this> */
    public function kategoriLomba(): HasMany
    {
        return $this->hasMany(KategoriLomba::class);
    }

    /** @return HasMany<Capaian, $this> */
    public function capaian(): HasMany
    {
        return $this->hasMany(Capaian::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['wajib_penyelenggara' => 'boolean', 'is_active' => 'boolean', 'urutan' => 'integer'];
    }
}

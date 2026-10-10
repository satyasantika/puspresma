<?php

namespace App\Models;

use App\Models\Concerns\ScopeMaster;
use App\Models\Concerns\TercatatAktivitas;
use App\Observers\MasterObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['kode', 'nama', 'urutan', 'kode_simkatmawa', 'is_active'])]
#[ObservedBy(MasterObserver::class)]
class Tingkat extends Model
{
    use HasUuids, ScopeMaster, SoftDeletes, TercatatAktivitas;

    protected $table = 'tingkat';

    protected $attributes = ['urutan' => 0, 'is_active' => true];

    protected function atributTercatat(): array
    {
        return ['kode', 'nama', 'urutan', 'kode_simkatmawa', 'is_active'];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['urutan' => 'integer', 'is_active' => 'boolean'];
    }
}

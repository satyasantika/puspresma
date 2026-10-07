<?php

namespace App\Models;

use App\Enums\JenjangProdi;
use App\Models\Concerns\TercatatAktivitas;
use Database\Factories\ProdiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['kode', 'kode_pddikti', 'nama', 'jenjang', 'kode_eksternal', 'is_active'])]
class Prodi extends Model
{
    /** @use HasFactory<ProdiFactory> */
    use HasFactory, HasUuids, SoftDeletes, TercatatAktivitas;

    protected $table = 'prodi';

    protected $attributes = ['jenjang' => 'S1', 'is_active' => true];

    protected function atributTercatat(): array
    {
        return ['kode', 'kode_pddikti', 'nama', 'jenjang', 'is_active'];
    }

    /** @return HasMany<Mahasiswa, $this> */
    public function mahasiswa(): HasMany
    {
        return $this->hasMany(Mahasiswa::class);
    }

    /** @return HasMany<Dosen, $this> */
    public function dosen(): HasMany
    {
        return $this->hasMany(Dosen::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['jenjang' => JenjangProdi::class, 'is_active' => 'boolean'];
    }
}

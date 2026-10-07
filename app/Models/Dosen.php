<?php

namespace App\Models;

use App\Models\Concerns\TercatatAktivitas;
use Database\Factories\DosenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nidn', 'nip', 'nama', 'gelar_depan', 'gelar_belakang', 'prodi_id', 'email', 'user_id', 'kode_eksternal'])]
class Dosen extends Model
{
    /** @use HasFactory<DosenFactory> */
    use HasFactory, HasUuids, SoftDeletes, TercatatAktivitas;

    protected $table = 'dosen';

    protected function atributTercatat(): array
    {
        return ['nidn', 'nip', 'nama', 'gelar_depan', 'gelar_belakang', 'prodi_id', 'email', 'user_id'];
    }

    /** @return Attribute<string, never> */
    protected function namaLengkap(): Attribute
    {
        return Attribute::get(fn (): string => trim(implode(' ', array_filter([
            $this->gelar_depan,
            $this->nama,
        ])).($this->gelar_belakang ? ', '.$this->gelar_belakang : '')));
    }

    /** @return BelongsTo<Prodi, $this> */
    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

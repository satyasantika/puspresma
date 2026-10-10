<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Penanda "berminat" mahasiswa pada kompetisi; dasar pengingat H-3/H-1 (F7.3). */
#[Fillable(['kompetisi_id', 'mahasiswa_id', 'diingatkan_h3_at', 'diingatkan_h1_at'])]
class KompetisiMinat extends Model
{
    use HasUuids;

    protected $table = 'kompetisi_minat';

    /** @return BelongsTo<Kompetisi, $this> */
    public function kompetisi(): BelongsTo
    {
        return $this->belongsTo(Kompetisi::class);
    }

    /** @return BelongsTo<Mahasiswa, $this> */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['diingatkan_h3_at' => 'datetime', 'diingatkan_h1_at' => 'datetime'];
    }
}

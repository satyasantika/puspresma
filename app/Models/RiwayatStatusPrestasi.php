<?php

namespace App\Models;

use App\Enums\StatusPrestasi;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat status (bukan JSON); hanya ditulis oleh TransisiStatus.
 *
 * @property StatusPrestasi|null $dari_status
 * @property StatusPrestasi $ke_status
 */
#[Fillable(['prestasi_id', 'dari_status', 'ke_status', 'user_id', 'catatan'])]
class RiwayatStatusPrestasi extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'riwayat_status_prestasi';

    /** @return BelongsTo<Prestasi, $this> */
    public function prestasi(): BelongsTo
    {
        return $this->belongsTo(Prestasi::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['dari_status' => StatusPrestasi::class, 'ke_status' => StatusPrestasi::class];
    }
}

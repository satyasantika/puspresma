<?php

namespace App\Models;

use App\Enums\AlasanDuplikat;
use App\Enums\StatusTinjauDuplikat;
use App\Models\Concerns\TercatatAktivitas;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property AlasanDuplikat $alasan
 * @property StatusTinjauDuplikat $status_tinjau
 * @property string $skor
 */
#[Fillable(['prestasi_id', 'pembanding_id', 'alasan', 'skor', 'status_tinjau', 'ditinjau_oleh', 'ditinjau_at', 'catatan'])]
class KandidatDuplikat extends Model
{
    use HasUuids, TercatatAktivitas;

    protected $table = 'kandidat_duplikat';

    protected $attributes = ['status_tinjau' => 'belum_ditinjau'];

    protected function namaLog(): string
    {
        return 'duplikat';
    }

    protected function atributTercatat(): array
    {
        return ['status_tinjau', 'ditinjau_oleh', 'ditinjau_at', 'catatan'];
    }

    /** @return BelongsTo<Prestasi, $this> */
    public function prestasi(): BelongsTo
    {
        return $this->belongsTo(Prestasi::class);
    }

    /** @return BelongsTo<Prestasi, $this> */
    public function pembanding(): BelongsTo
    {
        return $this->belongsTo(Prestasi::class, 'pembanding_id');
    }

    /** @return BelongsTo<User, $this> */
    public function peninjau(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'alasan' => AlasanDuplikat::class,
            'status_tinjau' => StatusTinjauDuplikat::class,
            'skor' => 'decimal:2',
            'ditinjau_at' => 'datetime',
        ];
    }
}

<?php

namespace App\Models;

use App\Enums\StatusImpor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property StatusImpor $status
 * @property Carbon|null $kedaluwarsa_at
 * @property int $total_baris
 * @property int $berhasil
 * @property int $gagal
 */
#[Fillable(['jenis', 'nama_berkas', 'path_tmp', 'status', 'total_baris', 'berhasil', 'gagal', 'path_galat', 'kedaluwarsa_at', 'pesan', 'user_id'])]
class RiwayatImpor extends Model
{
    use HasUuids;

    protected $table = 'riwayat_impor';

    protected $attributes = ['status' => 'antre', 'total_baris' => 0, 'berhasil' => 0, 'gagal' => 0];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Berkas galat masih dapat diunduh (belum kedaluwarsa). */
    public function galatTersedia(): bool
    {
        return $this->path_galat !== null && $this->kedaluwarsa_at !== null && $this->kedaluwarsa_at->isFuture();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => StatusImpor::class,
            'kedaluwarsa_at' => 'datetime',
            'total_baris' => 'integer',
            'berhasil' => 'integer',
            'gagal' => 'integer',
        ];
    }
}

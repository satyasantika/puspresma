<?php

namespace App\Models;

use App\Enums\StatusBerkasEkspor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property StatusBerkasEkspor $status
 * @property array<string, mixed>|null $parameter
 * @property Carbon|null $kedaluwarsa_at
 */
#[Fillable(['jenis', 'format', 'parameter', 'path', 'status', 'jumlah_baris', 'pesan_galat', 'user_id', 'kedaluwarsa_at'])]
class BerkasEkspor extends Model
{
    use HasUuids;

    protected $table = 'berkas_ekspor';

    protected $attributes = ['status' => 'antre'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kedaluwarsa(): bool
    {
        return $this->status === StatusBerkasEkspor::Kedaluwarsa
            || ($this->kedaluwarsa_at !== null && $this->kedaluwarsa_at->isPast());
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => StatusBerkasEkspor::class, 'parameter' => 'array', 'kedaluwarsa_at' => 'datetime'];
    }
}

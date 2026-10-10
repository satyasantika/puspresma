<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/** Salinan tautan bukti saat terverifikasi/penggantian (BR-28); tidak dapat diubah atau dihapus lewat aplikasi. */
#[Fillable(['prestasi_id', 'tautan_berkas_id', 'jenis', 'url', 'drive_file_id', 'status_cek', 'alasan', 'dibuka_verifikator', 'user_id'])]
class SnapshotTautanVerifikasi extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'snapshot_tautan_verifikasi';

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Snapshot tautan tidak dapat diubah.'));
        static::deleting(fn () => throw new LogicException('Snapshot tautan tidak dapat dihapus.'));
    }

    /** @return BelongsTo<Prestasi, $this> */
    public function prestasi(): BelongsTo
    {
        return $this->belongsTo(Prestasi::class);
    }

    /** @return BelongsTo<User, $this> */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['dibuka_verifikator' => 'boolean'];
    }
}

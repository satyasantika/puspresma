<?php

namespace App\Models;

use App\Enums\PeranAnggota;
use App\Models\Concerns\TercatatAktivitas;
use Database\Factories\PrestasiAnggotaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Anggota internal prestasi (kredit penuh, BR-02). Dipakai sebagai model dan pivot belongsToMany.
 *
 * @property PeranAnggota $peran
 * @property bool $is_pengaju
 * @property bool $setuju_publikasi
 * @property string|null $poin
 */
class PrestasiAnggota extends Pivot
{
    /** @use HasFactory<PrestasiAnggotaFactory> */
    use HasFactory, HasUuids, TercatatAktivitas;

    protected $table = 'prestasi_anggota';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $attributes = ['peran' => 'anggota', 'is_pengaju' => false, 'setuju_publikasi' => false];

    protected function namaLog(): string
    {
        return 'prestasi-anggota';
    }

    protected function atributTercatat(): array
    {
        return ['prestasi_id', 'mahasiswa_id', 'prodi_id', 'peran', 'is_pengaju', 'setuju_publikasi', 'setuju_publikasi_at', 'poin'];
    }

    /** @return BelongsTo<Prestasi, $this> */
    public function prestasi(): BelongsTo
    {
        return $this->belongsTo(Prestasi::class);
    }

    /** @return BelongsTo<Mahasiswa, $this> */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    /** @return BelongsTo<Prodi, $this> */
    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'peran' => PeranAnggota::class,
            'is_pengaju' => 'boolean',
            'setuju_publikasi' => 'boolean',
            'setuju_publikasi_at' => 'datetime',
            'poin' => 'decimal:2',
        ];
    }
}

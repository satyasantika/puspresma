<?php

namespace App\Models;

use App\Enums\StatusSurat;
use App\Models\Concerns\TercatatAktivitas;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property StatusSurat $status
 * @property Carbon $tanggal_terbit
 * @property Carbon|null $periode_mulai
 * @property Carbon|null $periode_selesai
 * @property Carbon|null $dicabut_at
 */
#[Fillable(['nomor', 'mahasiswa_id', 'keperluan', 'periode_mulai', 'periode_selesai', 'tanggal_terbit', 'penandatangan_nama', 'penandatangan_jabatan', 'penandatangan_nip', 'status', 'dicabut_at', 'alasan_dicabut', 'diterbitkan_oleh'])]
class SuratKeteranganPrestasi extends Model
{
    use HasUuids, TercatatAktivitas;

    protected $table = 'surat_keterangan_prestasi';

    protected $attributes = ['status' => 'aktif'];

    protected function namaLog(): string
    {
        return 'surat';
    }

    protected function atributTercatat(): array
    {
        return ['nomor', 'mahasiswa_id', 'keperluan', 'tanggal_terbit', 'status', 'alasan_dicabut', 'diterbitkan_oleh'];
    }

    /** @return BelongsTo<Mahasiswa, $this> */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    /** @return BelongsTo<User, $this> */
    public function penerbit(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterbitkan_oleh');
    }

    /** @return HasMany<SuratKeteranganPrestasiItem, $this> */
    public function item(): HasMany
    {
        return $this->hasMany(SuratKeteranganPrestasiItem::class);
    }

    /** @return BelongsToMany<Prestasi, $this> */
    public function prestasi(): BelongsToMany
    {
        return $this->belongsToMany(Prestasi::class, 'surat_keterangan_prestasi_item');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => StatusSurat::class,
            'tanggal_terbit' => 'date',
            'periode_mulai' => 'date',
            'periode_selesai' => 'date',
            'dicabut_at' => 'datetime',
        ];
    }
}

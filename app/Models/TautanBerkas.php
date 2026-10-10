<?php

namespace App\Models;

use App\Enums\JenisTautan;
use App\Enums\PenyediaTautan;
use App\Enums\StatusCekTautan;
use App\Models\Concerns\TercatatAktivitas;
use App\Observers\TautanBerkasObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Tautan berkas polimorfik (tanpa unggahan ke server, STANDAR-TEKNIS §1a).
 *
 * @property JenisTautan $jenis
 * @property PenyediaTautan $penyedia
 * @property StatusCekTautan $status_cek
 * @property Carbon|null $dicek_pada
 * @property bool $tampil_publik
 */
#[Fillable(['pemilik_type', 'pemilik_id', 'jenis', 'label', 'url', 'penyedia', 'drive_file_id', 'status_cek', 'dicek_pada', 'ditambahkan_oleh', 'tampil_publik', 'url_normal'])]
#[ObservedBy(TautanBerkasObserver::class)]
class TautanBerkas extends Model
{
    use HasUuids, SoftDeletes, TercatatAktivitas;

    protected $table = 'tautan_berkas';

    protected $attributes = ['status_cek' => 'belum', 'tampil_publik' => false];

    protected function namaLog(): string
    {
        return 'tautan';
    }

    /** Hanya kolom yang bermakna bagi audit; perubahan URL tercatat lama → baru. */
    protected function atributTercatat(): array
    {
        return ['url', 'jenis', 'label', 'tampil_publik'];
    }

    /** @return MorphTo<Model, $this> */
    public function pemilik(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function penambah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditambahkan_oleh');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'jenis' => JenisTautan::class,
            'penyedia' => PenyediaTautan::class,
            'status_cek' => StatusCekTautan::class,
            'dicek_pada' => 'datetime',
            'tampil_publik' => 'boolean',
        ];
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\TercatatAktivitas;
use App\Observers\BobotPoinObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $poin
 * @property Carbon $berlaku_mulai
 * @property Carbon|null $berlaku_sampai
 */
#[Fillable(['jenis_kegiatan_id', 'tingkat_id', 'capaian_id', 'poin', 'berlaku_mulai', 'berlaku_sampai', 'dasar_penetapan'])]
#[ObservedBy(BobotPoinObserver::class)]
class BobotPoin extends Model
{
    use HasUuids, TercatatAktivitas;

    protected $table = 'bobot_poin';

    protected function namaLog(): string
    {
        return 'bobot-poin';
    }

    protected function atributTercatat(): array
    {
        return ['jenis_kegiatan_id', 'tingkat_id', 'capaian_id', 'poin', 'berlaku_mulai', 'berlaku_sampai', 'dasar_penetapan'];
    }

    /** @return BelongsTo<JenisKegiatan, $this> */
    public function jenisKegiatan(): BelongsTo
    {
        return $this->belongsTo(JenisKegiatan::class);
    }

    /** @return BelongsTo<Tingkat, $this> */
    public function tingkat(): BelongsTo
    {
        return $this->belongsTo(Tingkat::class);
    }

    /** @return BelongsTo<Capaian, $this> */
    public function capaian(): BelongsTo
    {
        return $this->belongsTo(Capaian::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['poin' => 'decimal:2', 'berlaku_mulai' => 'date', 'berlaku_sampai' => 'date'];
    }
}

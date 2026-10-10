<?php

namespace App\Models;

use App\Models\Concerns\TercatatAktivitas;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['kunci', 'nilai', 'tipe', 'grup', 'keterangan'])]
class Pengaturan extends Model
{
    use HasUuids, TercatatAktivitas;

    protected $table = 'pengaturan';

    protected $attributes = ['tipe' => 'string', 'grup' => 'umum'];

    protected function namaLog(): string
    {
        return 'pengaturan';
    }

    protected function atributTercatat(): array
    {
        return ['kunci', 'nilai'];
    }

    /** Deskripsi memuat kunci agar log menunjukkan pengaturan mana yang berubah (nilai lama → baru di properti). */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->atributTercatat())
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $peristiwa): string => "Pengaturan {$this->kunci} {$peristiwa}")
            ->useLogName($this->namaLog());
    }
}

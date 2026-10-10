<?php

namespace App\Models;

use App\Enums\StatusMahasiswa;
use App\Models\Concerns\TercatatAktivitas;
use Database\Factories\MahasiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property StatusMahasiswa $status_mahasiswa
 */
#[Fillable(['npm', 'nama', 'prodi_id', 'angkatan', 'email', 'nomor_hp', 'status_mahasiswa', 'user_id', 'kode_eksternal'])]
class Mahasiswa extends Model
{
    /** @use HasFactory<MahasiswaFactory> */
    use HasFactory, HasUuids, SoftDeletes, TercatatAktivitas;

    protected $table = 'mahasiswa';

    protected $attributes = ['status_mahasiswa' => 'aktif'];

    /** nomor_hp sengaja tidak dicatat nilainya (BR-26). */
    protected function atributTercatat(): array
    {
        return ['npm', 'nama', 'prodi_id', 'angkatan', 'email', 'status_mahasiswa', 'user_id'];
    }

    /** @return BelongsTo<Prodi, $this> */
    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status_mahasiswa' => StatusMahasiswa::class, 'angkatan' => 'integer'];
    }
}

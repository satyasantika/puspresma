<?php

namespace App\Models;

use App\Enums\BidangPrestasi;
use App\Enums\ModaKegiatan;
use App\Enums\StatusKompetisi;
use App\Models\Concerns\TercatatAktivitas;
use App\Observers\KompetisiObserver;
use Database\Factories\KompetisiFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

/**
 * Pengumuman kompetisi (BR-23). Deskripsi rich text selalu disanitasi.
 *
 * @property StatusKompetisi $status
 * @property Carbon $tenggat_pendaftaran
 * @property string|null $biaya_pendaftaran
 * @property Carbon|null $diterbitkan_at
 * @property Carbon|null $tanggal_buka
 * @property Carbon|null $tanggal_pelaksanaan_mulai
 * @property Carbon|null $tanggal_pelaksanaan_selesai
 */
class Kompetisi extends Model
{
    /** @use HasFactory<KompetisiFactory> */
    use HasFactory, HasUuids, SoftDeletes, TercatatAktivitas;

    protected $table = 'kompetisi';

    protected $guarded = [];

    protected $attributes = ['status' => 'draf'];

    protected static function booted(): void
    {
        static::creating(function (self $kompetisi): void {
            $kompetisi->slug = self::slugUnik($kompetisi->judul);
        });

        static::observe(KompetisiObserver::class);
    }

    /** Slug dari judul; ditambah -2, -3, … bila sudah dipakai (termasuk yang terhapus). */
    public static function slugUnik(string $judul): string
    {
        $dasar = Str::slug($judul) ?: 'kompetisi';
        $slug = $dasar;
        for ($i = 2; self::withTrashed()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$dasar}-{$i}";
        }

        return $slug;
    }

    protected function namaLog(): string
    {
        return 'kompetisi';
    }

    protected function atributTercatat(): array
    {
        return ['judul', 'penyelenggara', 'status', 'tenggat_pendaftaran', 'url_pendaftaran', 'biaya_pendaftaran'];
    }

    /**
     * Menyaring HTML deskripsi dengan profil purifier 'deskripsi' setiap kali diisi.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function deskripsi(): Attribute
    {
        return Attribute::make(
            set: fn (?string $nilai): ?string => blank($nilai) ? null : Purifier::clean($nilai, 'deskripsi'),
        );
    }

    /** @param  Builder<static>  $query */
    public function scopeTerbit(Builder $query): void
    {
        $query->where($query->qualifyColumn('status'), StatusKompetisi::Terbit->value);
    }

    /**
     * Terbit dan tenggat pendaftaran belum lewat.
     *
     * @param  Builder<static>  $query
     */
    public function scopeMasihDibuka(Builder $query): void
    {
        $query->terbit()->where($query->qualifyColumn('tenggat_pendaftaran'), '>=', now());
    }

    /** @return BelongsTo<JenisKegiatan, $this> */
    public function jenisKegiatan(): BelongsTo
    {
        return $this->belongsTo(JenisKegiatan::class);
    }

    /** @return BelongsTo<KategoriLomba, $this> */
    public function kategoriLomba(): BelongsTo
    {
        return $this->belongsTo(KategoriLomba::class);
    }

    /** @return BelongsTo<Tingkat, $this> */
    public function tingkat(): BelongsTo
    {
        return $this->belongsTo(Tingkat::class);
    }

    /** @return BelongsTo<User, $this> */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * Prodi sasaran; tanpa baris = semua prodi.
     *
     * @return BelongsToMany<Prodi, $this, Pivot, 'pivot'>
     */
    public function prodiSasaran(): BelongsToMany
    {
        return $this->belongsToMany(Prodi::class, 'kompetisi_prodi');
    }

    /** @return HasMany<KompetisiMinat, $this> */
    public function minat(): HasMany
    {
        return $this->hasMany(KompetisiMinat::class);
    }

    /** @return HasMany<Prestasi, $this> */
    public function prestasi(): HasMany
    {
        return $this->hasMany(Prestasi::class);
    }

    /** @return MorphOne<TautanBerkas, $this> */
    public function poster(): MorphOne
    {
        return $this->morphOne(TautanBerkas::class, 'pemilik')->where('jenis', 'poster');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => StatusKompetisi::class,
            'bidang' => BidangPrestasi::class,
            'moda' => ModaKegiatan::class,
            'tanggal_buka' => 'date',
            'tenggat_pendaftaran' => 'datetime',
            'tanggal_pelaksanaan_mulai' => 'date',
            'tanggal_pelaksanaan_selesai' => 'date',
            'diterbitkan_at' => 'datetime',
            'biaya_pendaftaran' => 'decimal:2',
        ];
    }
}

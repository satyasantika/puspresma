<?php

namespace App\Models;

use App\Enums\BentukPartisipasi;
use App\Enums\BidangPrestasi;
use App\Enums\JenisTautan;
use App\Enums\ModaKegiatan;
use App\Enums\PenyediaTautan;
use App\Enums\StatusCekTautan;
use App\Enums\StatusKonfirmasiPembimbing;
use App\Enums\StatusPrestasi;
use App\Enums\StatusTinjauDuplikat;
use App\Models\Concerns\TercatatAktivitas;
use App\Services\NormalisasiTeks;
use App\Services\Pengaturan;
use Database\Factories\PrestasiFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Satu record = satu kegiatan + satu capaian; prestasi tim dicatat sekali dengan banyak anggota (BR-01).
 *
 * @property StatusPrestasi $status
 * @property BentukPartisipasi $bentuk
 * @property BidangPrestasi $bidang
 * @property ModaKegiatan $moda
 * @property StatusKonfirmasiPembimbing $status_konfirmasi_pembimbing
 * @property Carbon $tanggal_mulai
 * @property Carbon $tanggal_selesai
 * @property int $tahun
 */
class Prestasi extends Model
{
    /** @use HasFactory<PrestasiFactory> */
    use HasFactory, HasUuids, SoftDeletes, TercatatAktivitas;

    protected $table = 'prestasi';

    /** Kolom diisi lewat Action; status HANYA berubah lewat TransisiStatus. */
    protected $guarded = [];

    protected $attributes = [
        'status' => 'draf',
        'bentuk' => 'individu',
        'moda' => 'luring',
        'negara_penyelenggara' => 'Indonesia',
        'status_konfirmasi_pembimbing' => 'tidak_ada',
        'verifikasi_tanpa_konfirmasi' => false,
        'disembunyikan_dari_galeri' => false,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $prestasi): void {
            if ($prestasi->isDirty('tanggal_selesai') || blank($prestasi->tahun)) {
                $prestasi->tahun = (int) $prestasi->tanggal_selesai->format('Y');
            }
            if ($prestasi->isDirty('nama_kegiatan') || blank($prestasi->nama_kegiatan_normal)) {
                $prestasi->nama_kegiatan_normal = NormalisasiTeks::kegiatan((string) $prestasi->nama_kegiatan);
            }
        });
    }

    protected function namaLog(): string
    {
        return 'prestasi';
    }

    /** Semua kolom kecuali yang diturunkan (tahun, nama_kegiatan_normal) dan timestamp. */
    protected function atributTercatat(): array
    {
        return array_values(array_diff(
            array_keys($this->getAttributes()),
            ['id', 'tahun', 'nama_kegiatan_normal', 'created_at', 'updated_at', 'deleted_at'],
        ));
    }

    /** @param  Builder<static>  $query */
    public function scopeStatus(Builder $query, StatusPrestasi ...$status): void
    {
        $query->whereIn($query->qualifyColumn('status'), array_map(fn (StatusPrestasi $s) => $s->value, $status));
    }

    /** @param  Builder<static>  $query */
    public function scopeTerverifikasi(Builder $query): void
    {
        $query->where($query->qualifyColumn('status'), StatusPrestasi::Terverifikasi->value);
    }

    /**
     * Setara SQL dari tampilDiGaleri() tanpa pemeriksaan pengaturan global galeri_aktif (BR-16).
     *
     * @param  Builder<static>  $query
     */
    public function scopeTampilDiGaleri(Builder $query): void
    {
        $query->where($query->qualifyColumn('status'), StatusPrestasi::Terverifikasi->value)
            ->where($query->qualifyColumn('disembunyikan_dari_galeri'), false)
            ->whereHas('anggota', fn (Builder $q) => $q->where('is_pengaju', true)->where('setuju_publikasi', true));
    }

    /**
     * Prestasi yang memiliki minimal satu anggota dari prodi tersebut (BR-15).
     *
     * @param  Builder<static>  $query
     */
    public function scopeMelibatkanProdi(Builder $query, string $prodiId): void
    {
        $query->whereHas('anggota', fn (Builder $q) => $q->where('prodi_id', $prodiId));
    }

    /** @param  Builder<static>  $query */
    public function scopeMilikMahasiswa(Builder $query, string $mahasiswaId): void
    {
        $query->whereHas('anggota', fn (Builder $q) => $q->where('mahasiswa_id', $mahasiswaId));
    }

    /** @return BelongsTo<Mahasiswa, $this> */
    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class, 'pengaju_id');
    }

    /** @return BelongsTo<Prodi, $this> */
    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    /** @return BelongsTo<User, $this> */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /** @return BelongsTo<User, $this> */
    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
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

    /** @return BelongsTo<Capaian, $this> */
    public function capaian(): BelongsTo
    {
        return $this->belongsTo(Capaian::class);
    }

    /** @return BelongsTo<Dosen, $this> */
    public function dosenPembimbing(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_pembimbing_id');
    }

    /** @return HasMany<PrestasiAnggota, $this> */
    public function anggota(): HasMany
    {
        return $this->hasMany(PrestasiAnggota::class);
    }

    /** @return BelongsToMany<Mahasiswa, $this, PrestasiAnggota> */
    public function mahasiswa(): BelongsToMany
    {
        return $this->belongsToMany(Mahasiswa::class, 'prestasi_anggota')
            ->using(PrestasiAnggota::class)
            ->withPivot(['id', 'prodi_id', 'peran', 'is_pengaju', 'setuju_publikasi', 'setuju_publikasi_at', 'poin'])
            ->withTimestamps();
    }

    /** @return HasMany<PrestasiAnggotaEksternal, $this> */
    public function anggotaEksternal(): HasMany
    {
        return $this->hasMany(PrestasiAnggotaEksternal::class);
    }

    /**
     * Tautan bukti (bukan poster) pada tabel polimorfik tautan_berkas.
     *
     * @return MorphMany<TautanBerkas, $this>
     */
    public function tautanBukti(): MorphMany
    {
        return $this->morphMany(TautanBerkas::class, 'pemilik')->where('jenis', '!=', 'poster');
    }

    /** @return HasMany<SnapshotTautanVerifikasi, $this> */
    public function snapshotTautan(): HasMany
    {
        return $this->hasMany(SnapshotTautanVerifikasi::class)->orderBy('created_at');
    }

    /**
     * Tampil di galeri publik bila terverifikasi, galeri aktif, pengaju menyetujui, dan tidak disembunyikan (BR-16).
     */
    public function tampilDiGaleri(): bool
    {
        return $this->status === StatusPrestasi::Terverifikasi
            && (bool) Pengaturan::ambil('galeri_aktif', true)
            && ! $this->disembunyikan_dari_galeri
            && $this->anggota()->where('is_pengaju', true)->where('setuju_publikasi', true)->exists();
    }

    /**
     * Foto penyerahan yang boleh tampil publik: diizinkan mahasiswa, Google Drive dengan id, dan dapat diakses
     * anonim (artinya dibagikan publik). Tautan sertifikat/surat tugas tidak pernah masuk (BR-16).
     *
     * @return Collection<int, TautanBerkas>
     */
    public function fotoPublik(): Collection
    {
        return $this->tautanBukti()
            ->where('jenis', JenisTautan::FotoPenyerahan->value)
            ->where('tampil_publik', true)
            ->where('penyedia', PenyediaTautan::GoogleDrive->value)
            ->whereNotNull('drive_file_id')
            ->where('status_cek', StatusCekTautan::DapatDiakses->value)
            ->get();
    }

    /**
     * Alasan prestasi belum tampil di galeri (kosong = tampil).
     *
     * @return list<string>
     */
    public function alasanTidakTampilDiGaleri(): array
    {
        $alasan = [];
        if ($this->status !== StatusPrestasi::Terverifikasi) {
            $alasan[] = 'Prestasi belum terverifikasi.';
        }
        if (! Pengaturan::ambil('galeri_aktif', true)) {
            $alasan[] = 'Galeri sedang dinonaktifkan.';
        }
        if ($this->disembunyikan_dari_galeri) {
            $alasan[] = 'Disembunyikan oleh admin.';
        }
        if (! $this->anggota()->where('is_pengaju', true)->where('setuju_publikasi', true)->exists()) {
            $alasan[] = 'Pengaju belum menyetujui publikasi.';
        }

        return $alasan;
    }

    /**
     * Alasan foto belum tampil (kosong bila ada foto yang memenuhi syarat atau tidak ada foto).
     *
     * @return list<string>
     */
    public function alasanFotoTidakTampil(): array
    {
        $foto = $this->tautanBukti()->where('jenis', JenisTautan::FotoPenyerahan->value)->get();
        if ($foto->isEmpty() || $this->fotoPublik()->isNotEmpty()) {
            return [];
        }

        $alasan = [];
        if ($foto->where('tampil_publik', false)->isNotEmpty()) {
            $alasan[] = 'Foto belum diizinkan tampil.';
        }
        if ($foto->where('tampil_publik', true)->contains(fn (TautanBerkas $t) => $t->status_cek !== StatusCekTautan::DapatDiakses || $t->penyedia !== PenyediaTautan::GoogleDrive)) {
            $alasan[] = 'Foto tidak dibagikan publik (atau bukan dari Google Drive).';
        }

        return $alasan;
    }

    /** @return HasMany<KandidatDuplikat, $this> */
    public function kandidatDuplikat(): HasMany
    {
        return $this->hasMany(KandidatDuplikat::class);
    }

    /** Ada kandidat duplikat yang belum ditinjau; memblokir verifikasi (BR-10). */
    public function adaKandidatDuplikatTerbuka(): bool
    {
        return $this->kandidatDuplikat()->where('status_tinjau', StatusTinjauDuplikat::BelumDitinjau->value)->exists();
    }

    /** @return HasMany<RiwayatStatusPrestasi, $this> */
    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatusPrestasi::class)->orderBy('created_at');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => StatusPrestasi::class,
            'bentuk' => BentukPartisipasi::class,
            'bidang' => BidangPrestasi::class,
            'moda' => ModaKegiatan::class,
            'status_konfirmasi_pembimbing' => StatusKonfirmasiPembimbing::class,
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'tahun' => 'integer',
            'diajukan_at' => 'datetime',
            'diverifikasi_at' => 'datetime',
            'dikonfirmasi_pembimbing_at' => 'datetime',
            'dilaporkan_simkatmawa_at' => 'datetime',
            'verifikasi_tanpa_konfirmasi' => 'boolean',
            'disembunyikan_dari_galeri' => 'boolean',
        ];
    }
}

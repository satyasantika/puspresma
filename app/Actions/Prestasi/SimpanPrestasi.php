<?php

namespace App\Actions\Prestasi;

use App\Enums\BentukPartisipasi;
use App\Enums\BidangPrestasi;
use App\Enums\ModaKegiatan;
use App\Enums\PeranAnggota;
use App\Enums\StatusPrestasi;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\PrestasiAnggota;
use App\Models\RiwayatStatusPrestasi;
use App\Models\User;
use App\Services\OpsiMaster;
use App\Services\Pengaturan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Membuat/mengubah data inti prestasi (BR-03, BR-04 bagian kolom, BR-05, BR-14).
 * Aturan ditegakkan di sini agar tidak bergantung pada UI (dipakai juga panel admin atas nama mahasiswa).
 * Status tidak diubah di sini selain nilai awal `draf` saat dibuat; transisi lewat TransisiStatus.
 */
class SimpanPrestasi
{
    public const KOLOM = [
        'jenis_kegiatan_id', 'bidang', 'kategori_lomba_id', 'nama_kegiatan', 'judul_karya', 'penyelenggara', 'url_kegiatan',
        'tanggal_mulai', 'tanggal_selesai', 'moda', 'lokasi', 'negara_penyelenggara', 'deskripsi', 'tingkat_id', 'capaian_id',
        'capaian_keterangan', 'bentuk', 'jumlah_peserta', 'jumlah_pt_peserta', 'jumlah_negara_peserta', 'negara_peserta',
        'nomor_sertifikat', 'kompetisi_id',
    ];

    public function __construct(private readonly OpsiMaster $opsi) {}

    /**
     * @param  array<string, mixed>  $data  kolom prestasi; `pengaju_id` hanya dipakai pelaku dengan izin buat-atas-nama
     */
    public function jalankan(?Prestasi $prestasi, array $data, User $pelaku): Prestasi
    {
        $gate = Gate::forUser($pelaku);
        $prestasi === null ? $gate->authorize('create', Prestasi::class) : $gate->authorize('update', $prestasi);

        $nilai = $this->validasi($data);

        return DB::transaction(fn () => $prestasi === null
            ? $this->buat($nilai, $data, $pelaku)
            : tap($prestasi)->update($nilai));
    }

    /**
     * Peringatan BR-22 (tidak memblokir): kegiatan terlalu lama atau sebelum angkatan.
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public static function peringatan(array $data, ?Mahasiswa $mahasiswa = null): array
    {
        $hasil = [];
        $selesai = filled($data['tanggal_selesai'] ?? null) ? CarbonImmutable::parse($data['tanggal_selesai']) : null;

        if ($selesai === null) {
            return $hasil;
        }

        $batas = (int) Pengaturan::ambil('batas_klaim_bulan', 24);
        if ($selesai->lt(now()->subMonths($batas))) {
            $hasil[] = "Kegiatan berakhir lebih dari {$batas} bulan lalu; verifikator mungkin menanyakan alasannya.";
        }

        if ($mahasiswa !== null && $selesai->year < $mahasiswa->angkatan) {
            $hasil[] = "Tahun kegiatan ({$selesai->year}) lebih awal daripada angkatan Anda ({$mahasiswa->angkatan}).";
        }

        return $hasil;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validasi(array $data): array
    {
        $jenisId = (string) ($data['jenis_kegiatan_id'] ?? '');
        $kodeTingkat = filled($data['tingkat_id'] ?? null) ? $this->opsi->kodeTingkat((string) $data['tingkat_id']) : null;
        $perluLokasi = in_array($data['moda'] ?? null, [ModaKegiatan::Luring->value, ModaKegiatan::Hibrida->value], true);

        $validator = Validator::make($data, [
            'jenis_kegiatan_id' => ['required', Rule::in(array_keys($this->opsi->jenisKegiatan()))],
            'bidang' => ['required', Rule::enum(BidangPrestasi::class)],
            'kategori_lomba_id' => ['nullable', Rule::in(array_keys($this->opsi->kategoriLomba($jenisId ?: null)))],
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'judul_karya' => ['nullable', 'string', 'max:255'],
            'penyelenggara' => ['required', 'string', 'max:255'],
            'url_kegiatan' => ['nullable', 'url', 'max:500'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai', 'before_or_equal:today'],
            'moda' => ['required', Rule::enum(ModaKegiatan::class)],
            'lokasi' => [Rule::requiredIf($perluLokasi), 'nullable', 'string', 'max:150'],
            'negara_penyelenggara' => ['nullable', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'tingkat_id' => ['required', Rule::in(array_keys($this->opsi->tingkat()))],
            // Capaian harus milik jenis kegiatan terpilih (rekognisi tidak bisa dipakai untuk kompetisi).
            'capaian_id' => ['required', Rule::in($jenisId === '' ? [] : array_keys($this->opsi->capaian($jenisId)))],
            'capaian_keterangan' => ['nullable', 'string', 'max:150'],
            'bentuk' => ['required', Rule::enum(BentukPartisipasi::class)],
            'jumlah_peserta' => ['nullable', 'integer', 'min:1'],
            'jumlah_pt_peserta' => [Rule::requiredIf($kodeTingkat === 'nasional'), 'nullable', 'integer', 'min:1'],
            'jumlah_negara_peserta' => [Rule::requiredIf($kodeTingkat === 'internasional'), 'nullable', 'integer', 'min:1'],
            'negara_peserta' => [Rule::requiredIf($kodeTingkat === 'internasional'), 'nullable', 'string', 'max:2000'],
            'nomor_sertifikat' => ['nullable', 'string', 'max:100'],
            'kompetisi_id' => ['nullable', 'exists:kompetisi,id'],
        ], [], self::atribut());

        $nilai = collect($validator->validate())->only(self::KOLOM)
            ->map(fn ($v) => is_string($v) && trim($v) === '' ? null : $v)->all();
        $nilai['deskripsi'] = filled($nilai['deskripsi'] ?? null) ? strip_tags((string) $nilai['deskripsi']) : null;
        $nilai['negara_penyelenggara'] = filled($nilai['negara_penyelenggara'] ?? null) ? $nilai['negara_penyelenggara'] : 'Indonesia';

        // Kolom yang tidak berlaku bagi tingkat terpilih dikosongkan agar data konsisten.
        if ($kodeTingkat !== 'internasional') {
            $nilai['jumlah_negara_peserta'] = null;
            $nilai['negara_peserta'] = null;
        }

        return $nilai;
    }

    /**
     * @param  array<string, mixed>  $nilai
     * @param  array<string, mixed>  $data
     */
    private function buat(array $nilai, array $data, User $pelaku): Prestasi
    {
        $pengaju = $this->tentukanPengaju($data, $pelaku);

        $prestasi = new Prestasi($nilai);
        $prestasi->forceFill([
            'pengaju_id' => $pengaju->id,
            'prodi_id' => $pengaju->prodi_id,
            'dibuat_oleh' => $pelaku->id,
            'status' => StatusPrestasi::Draf,
        ])->save();

        PrestasiAnggota::create([
            'prestasi_id' => $prestasi->id,
            'mahasiswa_id' => $pengaju->id,
            'prodi_id' => $pengaju->prodi_id,
            'peran' => PeranAnggota::Ketua,
            'is_pengaju' => true,
        ]);

        RiwayatStatusPrestasi::create([
            'prestasi_id' => $prestasi->id,
            'dari_status' => null,
            'ke_status' => StatusPrestasi::Draf,
            'user_id' => $pelaku->id,
            'catatan' => 'Prestasi dibuat.',
        ]);

        return $prestasi;
    }

    /** @param  array<string, mixed>  $data */
    private function tentukanPengaju(array $data, User $pelaku): Mahasiswa
    {
        if (filled($data['pengaju_id'] ?? null) && $pelaku->can('prestasi.buat-atas-nama')) {
            return Mahasiswa::findOrFail($data['pengaju_id']);
        }

        $pelaku->loadMissing('mahasiswa');

        return $pelaku->mahasiswa
            ?? throw ValidationException::withMessages(['pengaju_id' => 'Akun Anda tidak terhubung dengan data mahasiswa.']);
    }

    /** @return array<string, string> */
    private static function atribut(): array
    {
        return [
            'jenis_kegiatan_id' => 'jenis kegiatan', 'kategori_lomba_id' => 'kategori lomba', 'nama_kegiatan' => 'nama kegiatan',
            'judul_karya' => 'judul karya', 'url_kegiatan' => 'tautan kegiatan', 'tanggal_mulai' => 'tanggal mulai',
            'tanggal_selesai' => 'tanggal selesai', 'negara_penyelenggara' => 'negara penyelenggara', 'tingkat_id' => 'tingkat',
            'capaian_id' => 'capaian', 'capaian_keterangan' => 'keterangan capaian', 'jumlah_peserta' => 'jumlah peserta',
            'jumlah_pt_peserta' => 'jumlah perguruan tinggi peserta', 'jumlah_negara_peserta' => 'jumlah negara peserta',
            'negara_peserta' => 'daftar negara peserta', 'nomor_sertifikat' => 'nomor sertifikat',
        ];
    }
}

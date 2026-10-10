<?php

namespace App\Actions\Bukti;

use App\Contracts\PenyimpananBerkas;
use App\Enums\JenisTautan;
use App\Models\Prestasi;
use App\Models\TautanBerkas;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Menambah tautan bukti prestasi (BR-06, BR-14, BR-16); tidak ada berkas yang diunggah ke server. */
class SimpanTautanBukti
{
    public const MAKS_PER_PRESTASI = 10;

    /** Jenis yang boleh menjadi bukti prestasi (poster milik kompetisi). */
    public const JENIS_BUKTI = [JenisTautan::SertifikatPiagam, JenisTautan::FotoPenyerahan, JenisTautan::SuratTugas, JenisTautan::Lainnya];

    public function __construct(private readonly PenyimpananBerkas $penyimpanan) {}

    public function jalankan(Prestasi $prestasi, JenisTautan $jenis, string $url, ?string $label, bool $tampilPublik, User $pelaku): TautanBerkas
    {
        Gate::forUser($pelaku)->authorize('kelolaBukti', $prestasi);

        if (! in_array($jenis, self::JENIS_BUKTI, true)) {
            throw ValidationException::withMessages(['jenis' => 'Jenis tautan tidak dikenal untuk bukti prestasi.']);
        }

        return DB::transaction(function () use ($prestasi, $jenis, $url, $label, $tampilPublik, $pelaku): TautanBerkas {
            // Kunci baris prestasi agar dua penyimpanan paralel tidak melewati batas.
            Prestasi::whereKey($prestasi->getKey())->lockForUpdate()->first();

            if ($prestasi->tautanBukti()->count() >= self::MAKS_PER_PRESTASI) {
                throw ValidationException::withMessages(['url' => 'Maksimal '.self::MAKS_PER_PRESTASI.' tautan bukti per prestasi.']);
            }

            $tautan = $this->penyimpanan->simpanTautan($prestasi, $jenis, $url, $label, $pelaku);

            // Izin tampil di galeri hanya bermakna untuk foto penyerahan (BR-16).
            if ($tampilPublik && $jenis === JenisTautan::FotoPenyerahan) {
                $tautan->update(['tampil_publik' => true]);
            }

            return $tautan;
        });
    }
}

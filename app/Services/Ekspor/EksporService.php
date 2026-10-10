<?php

namespace App\Services\Ekspor;

use App\Enums\StatusBerkasEkspor;
use App\Jobs\EksporBerkasJob;
use App\Models\BerkasEkspor;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Titik masuk ekspor (BR-25): ≤ 2.000 baris di-stream langsung tanpa menulis berkas; lebih besar lewat antrean
 * `ekspor` ke storage/app/tmp (≤ 24 jam) dengan lock anti klik ganda dan pembatas laju.
 */
class EksporService
{
    public const BATAS_STREAM = 2000;

    /**
     * @param  array<string, mixed>  $filter
     */
    public function minta(LaporanEkspor $laporan, string $format, array $filter, User $pengguna): StreamedResponse|BerkasEkspor
    {
        if (! $pengguna->can($laporan->izin()) || ! in_array($format, $laporan->format(), true)) {
            throw new AuthorizationException('Anda tidak berhak mengekspor laporan ini.');
        }

        $jumlah = $laporan->jumlah($filter, $pengguna);
        $langsung = $jumlah <= (int) config('puspresma.ekspor.batas_stream', self::BATAS_STREAM);

        activity('ekspor')->causedBy($pengguna)->event($langsung ? 'langsung' : 'antrean')
            ->withProperties(['jenis' => $laporan->jenis(), 'format' => $format, 'filter' => $filter, 'jumlah_baris' => $jumlah])
            ->log("Permintaan ekspor {$laporan->judul()}");

        return $langsung
            ? $this->stream($laporan, $format, $filter, $pengguna)
            : $this->antre($laporan, $format, $filter, $pengguna, $jumlah);
    }

    /** @param  array<string, mixed>  $filter */
    private function stream(LaporanEkspor $laporan, string $format, array $filter, User $pengguna): StreamedResponse
    {
        $nama = "{$laporan->jenis()}-".now()->format('Ymd-His').".{$format}";
        $header = $laporan->header($pengguna);

        return response()->streamDownload(function () use ($laporan, $format, $filter, $pengguna, $header): void {
            if ($format === 'pdf') {
                echo PenulisBerkas::pdf($laporan, $pengguna, $filter, $header, $laporan->baris($filter, $pengguna));

                return;
            }
            PenulisBerkas::tabel('php://output', $format, $header, $laporan->baris($filter, $pengguna));
        }, $nama, $format === 'pdf' ? ['Content-Type' => 'application/pdf'] : []);
    }

    /** @param  array<string, mixed>  $filter */
    private function antre(LaporanEkspor $laporan, string $format, array $filter, User $pengguna, int $jumlah): BerkasEkspor
    {
        // Klik ganda dalam 2 menit hanya membuat satu job.
        if (! Cache::lock("lock:ekspor:{$pengguna->getKey()}:{$laporan->jenis()}", 120)->get()) {
            throw ValidationException::withMessages(['ekspor' => 'Ekspor sejenis sedang diproses; tunggu notifikasi "Berkas siap".']);
        }

        $kunci = 'ekspor:'.$pengguna->getKey();
        if (RateLimiter::tooManyAttempts($kunci, 10)) {
            throw ValidationException::withMessages(['ekspor' => 'Terlalu banyak ekspor besar; coba lagi nanti.']);
        }
        RateLimiter::hit($kunci, 3600);

        $berkas = BerkasEkspor::create([
            'jenis' => $laporan->jenis(), 'format' => $format, 'parameter' => $filter, 'status' => StatusBerkasEkspor::Antre,
            'jumlah_baris' => $jumlah, 'user_id' => $pengguna->getKey(),
        ]);

        EksporBerkasJob::dispatch($berkas);

        return $berkas;
    }
}

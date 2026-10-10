<?php

namespace App\Console\Commands;

use App\Models\KandidatDuplikat;
use App\Models\Prestasi;
use App\Models\User;
use App\Notifications\RingkasanAntreanVerifikasi;
use App\Services\Pengaturan;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class RingkasanAntrean extends Command
{
    protected $signature = 'puspresma:ringkasan-antrean';

    protected $description = 'Kirim ringkasan antrean verifikasi ke admin-fakultas dan admin-prodi (tidak dikirim bila kosong)';

    public function handle(): int
    {
        $terkirim = 0;

        $semua = $this->ringkas(Prestasi::query());
        if ($this->adaIsi($semua)) {
            $penerima = User::role('admin-fakultas')->where('is_active', true)->get();
            Notification::send($penerima, new RingkasanAntreanVerifikasi($semua));
            $terkirim += $penerima->count();
        }

        // Admin-prodi hanya bila tahap 1 aktif, dan hanya prodinya.
        if (Pengaturan::ambil('verifikasi_prodi_aktif', false)) {
            foreach (User::role('admin-prodi')->where('is_active', true)->whereNotNull('prodi_id')->get() as $admin) {
                $ringkasan = $this->ringkas(Prestasi::query()->where('prodi_id', $admin->prodi_id));
                if ($this->adaIsi($ringkasan)) {
                    Notification::send($admin, new RingkasanAntreanVerifikasi($ringkasan));
                    $terkirim++;
                }
            }
        }

        $this->info("{$terkirim} ringkasan dikirim.");

        return self::SUCCESS;
    }

    /**
     * @param  Builder<Prestasi>  $dasar
     * @return array{diajukan: int, lolos_prodi: int, duplikat_terbuka: int, pembimbing_menunggu: int, tertua_hari: ?int}
     */
    private function ringkas(Builder $dasar): array
    {
        $antrean = (clone $dasar)->whereIn('status', ['diajukan', 'lolos_prodi']);
        $tertua = (clone $antrean)->min('diajukan_at');

        return [
            'diajukan' => (clone $dasar)->where('status', 'diajukan')->count(),
            'lolos_prodi' => (clone $dasar)->where('status', 'lolos_prodi')->count(),
            'duplikat_terbuka' => KandidatDuplikat::where('status_tinjau', 'belum_ditinjau')->whereIn('prestasi_id', (clone $antrean)->select('id'))->count(),
            'pembimbing_menunggu' => (clone $antrean)->where('status_konfirmasi_pembimbing', 'menunggu')->count(),
            'tertua_hari' => $tertua === null ? null : (int) floor(Carbon::parse($tertua)->diffInDays(now())),
        ];
    }

    /** @param  array{diajukan: int, lolos_prodi: int, duplikat_terbuka: int, pembimbing_menunggu: int, tertua_hari: ?int}  $r */
    private function adaIsi(array $r): bool
    {
        return $r['diajukan'] + $r['lolos_prodi'] + $r['duplikat_terbuka'] + $r['pembimbing_menunggu'] > 0;
    }
}

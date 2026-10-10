<?php

namespace App\Listeners;

use App\Events\AnggotaTimDitambahkan;
use App\Events\KandidatDuplikatDitemukan;
use App\Events\PembimbingMenjawab;
use App\Events\TautanBerkasTidakDapatDiakses;
use App\Models\Prestasi;
use App\Models\User;
use App\Notifications\AnggotaTimDitambahkan as NotifAnggotaDitambahkan;
use App\Notifications\KandidatDuplikatDitemukan as NotifKandidatDuplikat;
use App\Notifications\PembimbingMenyangkal;
use App\Notifications\TautanBerkasTidakDapatDiakses as NotifTautanMati;
use App\Services\PenerimaNotifikasi;
use Illuminate\Support\Facades\Notification;

/** Pendengar notifikasi untuk peristiwa non-status. */
class KirimNotifikasiLainnya
{
    public function __construct(private readonly PenerimaNotifikasi $penerima) {}

    public function tautanTidakDapatDiakses(TautanBerkasTidakDapatDiakses $event): void
    {
        $prestasi = $event->tautan->pemilik;
        if (! $prestasi instanceof Prestasi) {
            return;
        }

        $penerima = collect(array_filter([$this->penerima->pengaju($prestasi)]));
        // Prestasi terverifikasi: admin-fakultas juga diberi tahu (BR-30).
        if ($prestasi->status->value === 'terverifikasi') {
            $penerima = $penerima->merge($this->penerima->adminFakultas())->unique('id');
        }

        Notification::send($penerima, new NotifTautanMati($event->tautan, $prestasi));
    }

    public function anggotaDitambahkan(AnggotaTimDitambahkan $event): void
    {
        $akun = $event->anggota->user_id === null ? null : User::find($event->anggota->user_id);

        if ($akun === null) {
            return;
        }

        Notification::send($akun, new NotifAnggotaDitambahkan($event->prestasi));
    }

    public function kandidatDuplikat(KandidatDuplikatDitemukan $event): void
    {
        Notification::send($this->penerima->adminFakultas(), new NotifKandidatDuplikat($event->prestasi, $event->jumlahBaru));
    }

    public function pembimbingMenjawab(PembimbingMenjawab $event): void
    {
        if ($event->dikonfirmasi) {
            return;
        }

        $penerima = $this->penerima->adminFakultas()->merge(array_filter([$this->penerima->pengaju($event->prestasi)]))->unique('id');
        Notification::send($penerima, new PembimbingMenyangkal($event->prestasi));
    }
}

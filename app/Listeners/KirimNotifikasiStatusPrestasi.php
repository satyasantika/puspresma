<?php

namespace App\Listeners;

use App\Enums\StatusKonfirmasiPembimbing;
use App\Enums\StatusPrestasi;
use App\Events\StatusPrestasiBerubah;
use App\Notifications\PermintaanKonfirmasiPembimbing;
use App\Notifications\PrestasiDiajukan;
use App\Notifications\PrestasiDitolak;
use App\Notifications\PrestasiPerluPerbaikan;
use App\Notifications\PrestasiTerverifikasi;
use App\Services\PenerimaNotifikasi;
use Illuminate\Support\Facades\Notification;

/** Mengirim notifikasi sesuai transisi status (dipanggil setelah commit; pengiriman sendiri lewat antrean). */
class KirimNotifikasiStatusPrestasi
{
    public function __construct(private readonly PenerimaNotifikasi $penerima) {}

    public function handle(StatusPrestasiBerubah $event): void
    {
        $prestasi = $event->prestasi;

        match ($event->ke) {
            StatusPrestasi::Diajukan => $this->diajukan($event),
            StatusPrestasi::PerluPerbaikan => Notification::send($this->penerima->anggotaInternal($prestasi), new PrestasiPerluPerbaikan($prestasi, $prestasi->catatan_verifikasi)),
            StatusPrestasi::Terverifikasi => Notification::send(
                $this->penerima->anggotaInternal($prestasi)->merge(array_filter([$this->penerima->pembimbing($prestasi)]))->unique('id'),
                new PrestasiTerverifikasi($prestasi),
            ),
            StatusPrestasi::Ditolak => ($pengaju = $this->penerima->pengaju($prestasi)) === null
                ? null
                : Notification::send($pengaju, new PrestasiDitolak($prestasi, $prestasi->catatan_verifikasi)),
            default => null,
        };
    }

    private function diajukan(StatusPrestasiBerubah $event): void
    {
        $prestasi = $event->prestasi;
        Notification::send($this->penerima->antreanVerifikasi($prestasi), new PrestasiDiajukan($prestasi));

        if ($prestasi->status_konfirmasi_pembimbing === StatusKonfirmasiPembimbing::Menunggu && ($dosen = $this->penerima->pembimbing($prestasi)) !== null) {
            Notification::send($dosen, new PermintaanKonfirmasiPembimbing($prestasi));
        }
    }
}

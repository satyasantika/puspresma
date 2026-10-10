<?php

namespace App\Notifications;

use App\Models\Prestasi;
use App\Models\User;
use App\Notifications\Concerns\BisaWhatsApp;

class PrestasiTerverifikasi extends NotifikasiAplikasi
{
    use BisaWhatsApp;

    public function __construct(public Prestasi $prestasi)
    {
        parent::__construct();
    }

    protected function kanal(): array
    {
        return ['database', 'mail'];
    }

    protected function judul(): string
    {
        return 'Prestasi terverifikasi';
    }

    protected function isi(User $penerima): string
    {
        return "Selamat! Prestasi \"{$this->prestasi->nama_kegiatan}\" telah terverifikasi.";
    }

    protected function url(User $penerima): ?string
    {
        return $this->urlPrestasi($penerima, $this->prestasi);
    }
}

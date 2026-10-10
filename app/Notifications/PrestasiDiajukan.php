<?php

namespace App\Notifications;

use App\Models\Prestasi;
use App\Models\User;

class PrestasiDiajukan extends NotifikasiAplikasi
{
    public function __construct(public Prestasi $prestasi)
    {
        parent::__construct();
    }

    protected function kanal(): array
    {
        return ['database'];
    }

    protected function judul(): string
    {
        return 'Prestasi baru menunggu verifikasi';
    }

    protected function isi(User $penerima): string
    {
        return "{$this->prestasi->nama_kegiatan} ({$this->prestasi->nomor_registrasi}) telah diajukan.";
    }

    protected function url(User $penerima): ?string
    {
        return $this->urlPrestasi($penerima, $this->prestasi);
    }
}

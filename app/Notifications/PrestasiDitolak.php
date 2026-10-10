<?php

namespace App\Notifications;

use App\Models\Prestasi;
use App\Models\User;

class PrestasiDitolak extends NotifikasiAplikasi
{
    public function __construct(public Prestasi $prestasi, public ?string $catatan)
    {
        parent::__construct();
    }

    protected function kanal(): array
    {
        return ['database', 'mail'];
    }

    protected function judul(): string
    {
        return 'Pengajuan prestasi ditolak';
    }

    protected function isi(User $penerima): string
    {
        return "Prestasi \"{$this->prestasi->nama_kegiatan}\" ditolak. Alasan: {$this->catatan}";
    }

    protected function url(User $penerima): ?string
    {
        return $this->urlPrestasi($penerima, $this->prestasi);
    }
}

<?php

namespace App\Notifications;

use App\Models\Prestasi;
use App\Models\User;

class PrestasiPerluPerbaikan extends NotifikasiAplikasi
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
        return 'Prestasi Anda perlu perbaikan';
    }

    protected function isi(User $penerima): string
    {
        return "Prestasi \"{$this->prestasi->nama_kegiatan}\" dikembalikan oleh verifikator. Catatan verifikator: {$this->catatan}";
    }

    protected function url(User $penerima): ?string
    {
        return $this->urlPrestasi($penerima, $this->prestasi);
    }
}

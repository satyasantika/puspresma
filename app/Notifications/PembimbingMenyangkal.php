<?php

namespace App\Notifications;

use App\Models\Prestasi;
use App\Models\User;

class PembimbingMenyangkal extends NotifikasiAplikasi
{
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
        return 'Dosen pembimbing menyangkal pembimbingan';
    }

    protected function isi(User $penerima): string
    {
        return "Pembimbing pada \"{$this->prestasi->nama_kegiatan}\" menyangkal. Alasan: {$this->prestasi->catatan_pembimbing}";
    }

    protected function url(User $penerima): ?string
    {
        return $this->urlPrestasi($penerima, $this->prestasi);
    }
}

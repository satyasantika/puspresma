<?php

namespace App\Notifications;

use App\Models\Prestasi;
use App\Models\User;

class KandidatDuplikatDitemukan extends NotifikasiAplikasi
{
    public function __construct(public Prestasi $prestasi, public int $jumlah)
    {
        parent::__construct();
    }

    protected function kanal(): array
    {
        return ['database'];
    }

    protected function judul(): string
    {
        return 'Kandidat duplikat ditemukan';
    }

    protected function isi(User $penerima): string
    {
        return "{$this->jumlah} kandidat duplikat untuk \"{$this->prestasi->nama_kegiatan}\" perlu ditinjau.";
    }

    protected function url(User $penerima): ?string
    {
        return $this->urlPrestasi($penerima, $this->prestasi);
    }
}

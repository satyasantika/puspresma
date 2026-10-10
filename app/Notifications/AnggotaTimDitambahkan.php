<?php

namespace App\Notifications;

use App\Filament\Portal\Pages\PersetujuanPublikasi;
use App\Models\Prestasi;
use App\Models\User;

class AnggotaTimDitambahkan extends NotifikasiAplikasi
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
        return 'Anda dicantumkan dalam tim prestasi';
    }

    protected function isi(User $penerima): string
    {
        return "Anda dicantumkan sebagai anggota tim pada \"{$this->prestasi->nama_kegiatan}\". Bila keliru, laporkan ke pengaju atau admin. Anda juga dapat mengatur persetujuan publikasi.";
    }

    protected function url(User $penerima): ?string
    {
        return PersetujuanPublikasi::getUrl(panel: 'portal');
    }
}

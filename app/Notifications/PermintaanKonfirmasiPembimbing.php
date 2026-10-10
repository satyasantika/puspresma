<?php

namespace App\Notifications;

use App\Filament\Portal\Resources\KonfirmasiPembimbingResource;
use App\Models\Prestasi;
use App\Models\User;

class PermintaanKonfirmasiPembimbing extends NotifikasiAplikasi
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
        return 'Permintaan konfirmasi dosen pembimbing';
    }

    protected function isi(User $penerima): string
    {
        return "Anda dicantumkan sebagai pembimbing pada \"{$this->prestasi->nama_kegiatan}\". Mohon konfirmasi atau sangkal.";
    }

    protected function url(User $penerima): ?string
    {
        return KonfirmasiPembimbingResource::getUrl('index', panel: 'portal');
    }
}

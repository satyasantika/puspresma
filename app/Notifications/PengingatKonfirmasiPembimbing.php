<?php

namespace App\Notifications;

use App\Filament\Portal\Resources\KonfirmasiPembimbingResource;
use App\Models\User;

class PengingatKonfirmasiPembimbing extends NotifikasiAplikasi
{
    public function __construct(public int $jumlah)
    {
        parent::__construct();
    }

    protected function kanal(): array
    {
        return ['mail'];
    }

    protected function judul(): string
    {
        return 'Pengingat: permintaan konfirmasi pembimbingan';
    }

    protected function isi(User $penerima): string
    {
        return "Ada {$this->jumlah} permintaan konfirmasi pembimbingan yang menunggu lebih dari 7 hari.";
    }

    protected function url(User $penerima): ?string
    {
        return KonfirmasiPembimbingResource::getUrl('index', panel: 'portal');
    }
}

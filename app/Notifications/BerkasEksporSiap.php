<?php

namespace App\Notifications;

use App\Models\User;

/** Hasil ekspor besar siap diunduh (dibuat oleh antrean `ekspor`). */
class BerkasEksporSiap extends NotifikasiAplikasi
{
    public function __construct(public string $namaBerkas, public ?string $urlUnduh = null)
    {
        parent::__construct();
    }

    protected function kanal(): array
    {
        return ['database'];
    }

    protected function judul(): string
    {
        return 'Berkas ekspor siap diunduh';
    }

    protected function isi(User $penerima): string
    {
        return "{$this->namaBerkas} sudah siap dan tersedia selama 24 jam.";
    }

    protected function url(User $penerima): ?string
    {
        return $this->urlUnduh;
    }
}

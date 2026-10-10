<?php

namespace App\Notifications;

use App\Models\Kompetisi;
use App\Models\User;

class KompetisiBaruTerbit extends NotifikasiAplikasi
{
    public function __construct(public Kompetisi $kompetisi)
    {
        parent::__construct();
    }

    protected function kanal(): array
    {
        return ['database'];
    }

    protected function judul(): string
    {
        return 'Kompetisi baru dibuka';
    }

    protected function isi(User $penerima): string
    {
        return "{$this->kompetisi->judul} — tenggat {$this->kompetisi->tenggat_pendaftaran->translatedFormat('d F Y')}.";
    }

    protected function url(User $penerima): ?string
    {
        return route('kompetisi.show', $this->kompetisi->slug);
    }
}

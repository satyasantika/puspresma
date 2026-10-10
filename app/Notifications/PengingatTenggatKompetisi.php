<?php

namespace App\Notifications;

use App\Models\Kompetisi;
use App\Models\User;
use App\Notifications\Concerns\BisaWhatsApp;

class PengingatTenggatKompetisi extends NotifikasiAplikasi
{
    use BisaWhatsApp;

    public function __construct(public Kompetisi $kompetisi, public int $hariLagi)
    {
        parent::__construct();
    }

    protected function kanal(): array
    {
        return ['database', 'mail'];
    }

    protected function judul(): string
    {
        return "Pendaftaran {$this->kompetisi->judul} ditutup {$this->hariLagi} hari lagi";
    }

    protected function isi(User $penerima): string
    {
        return "Tenggat pendaftaran: {$this->kompetisi->tenggat_pendaftaran->translatedFormat('d F Y H:i')}. Anda menandai berminat pada kompetisi ini.";
    }

    protected function url(User $penerima): ?string
    {
        return route('kompetisi.show', $this->kompetisi->slug);
    }
}

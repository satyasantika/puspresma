<?php

namespace App\Notifications;

use App\Models\Prestasi;
use App\Models\TautanBerkas;
use App\Models\User;

class TautanBerkasTidakDapatDiakses extends NotifikasiAplikasi
{
    public function __construct(public TautanBerkas $tautan, public Prestasi $prestasi)
    {
        parent::__construct();
    }

    protected function kanal(): array
    {
        return ['database', 'mail'];
    }

    protected function judul(): string
    {
        return 'Tautan bukti tidak dapat diakses';
    }

    protected function isi(User $penerima): string
    {
        return "Tautan {$this->tautan->jenis->label()} pada \"{$this->prestasi->nama_kegiatan}\" tidak dapat diakses. Periksa pengaturan berbagi di Google Drive.";
    }

    protected function url(User $penerima): ?string
    {
        return $this->urlPrestasi($penerima, $this->prestasi);
    }
}

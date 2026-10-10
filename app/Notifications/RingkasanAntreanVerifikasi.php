<?php

namespace App\Notifications;

use App\Filament\Admin\Resources\PrestasiResource;
use App\Models\User;

class RingkasanAntreanVerifikasi extends NotifikasiAplikasi
{
    /** @param  array{diajukan: int, lolos_prodi: int, duplikat_terbuka: int, pembimbing_menunggu: int, tertua_hari: ?int}  $ringkasan */
    public function __construct(public array $ringkasan)
    {
        parent::__construct();
    }

    protected function kanal(): array
    {
        return ['mail'];
    }

    protected function judul(): string
    {
        return 'Ringkasan antrean verifikasi prestasi';
    }

    protected function isi(User $penerima): string
    {
        $r = $this->ringkasan;

        return "Menunggu verifikasi: {$r['diajukan']} diajukan, {$r['lolos_prodi']} lolos tahap prodi. "
            ."Kandidat duplikat terbuka: {$r['duplikat_terbuka']}. Konfirmasi pembimbing menunggu: {$r['pembimbing_menunggu']}."
            .($r['tertua_hari'] !== null ? " Pengajuan tertua menunggu {$r['tertua_hari']} hari." : '');
    }

    protected function url(User $penerima): ?string
    {
        return PrestasiResource::getUrl('index', panel: 'admin');
    }
}

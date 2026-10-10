<?php

namespace App\Livewire\Publik;

use App\Enums\StatusKompetisi;
use App\Models\Kompetisi;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Detail kompetisi. Keputusan: kompetisi diarsipkan tetap dapat dibuka lewat tautan langsung (dengan label
 * "Pendaftaran ditutup"); draf tidak pernah tampil (404).
 */
#[Layout('components.layouts.publik')]
class DetailKompetisi extends Component
{
    public Kompetisi $kompetisi;

    public function mount(Kompetisi $kompetisi): void
    {
        abort_if($kompetisi->status === StatusKompetisi::Draf, 404);

        $this->kompetisi = $kompetisi->load(['tingkat', 'jenisKegiatan', 'kategoriLomba', 'poster', 'prodiSasaran']);
    }

    public function render(): View
    {
        return view('livewire.publik.detail-kompetisi', [
            'ditutup' => $this->kompetisi->status !== StatusKompetisi::Terbit || $this->kompetisi->tenggat_pendaftaran->isPast(),
        ]);
    }
}

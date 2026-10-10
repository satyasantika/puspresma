<?php

namespace App\Livewire\Publik;

use App\Models\Prestasi;
use App\Services\GaleriPublik;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.publik')]
class DetailPrestasiPublik extends Component
{
    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(Prestasi $prestasi, GaleriPublik $galeri): void
    {
        $detail = $galeri->detail($prestasi);
        abort_if($detail === null, 404);

        $this->data = $detail;
    }

    public function render(): View
    {
        return view('livewire.publik.detail-prestasi-publik');
    }
}

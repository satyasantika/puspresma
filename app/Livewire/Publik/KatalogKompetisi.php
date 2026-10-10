<?php

namespace App\Livewire\Publik;

use App\Models\Prodi;
use App\Services\KatalogPublik;
use App\Services\OpsiMaster;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Katalog kompetisi publik: terbit dan masih dibuka, tenggat terdekat lebih dulu. */
#[Layout('components.layouts.publik')]
class KatalogKompetisi extends Component
{
    #[Url(as: 'cari')]
    public string $q = '';

    #[Url]
    public ?string $jenis = null;

    #[Url]
    public ?string $tingkat = null;

    #[Url]
    public ?string $kategori = null;

    #[Url]
    public ?string $prodi = null;

    #[Url(as: 'halaman')]
    public int $halaman = 1;

    public function updated(string $properti): void
    {
        if ($properti !== 'halaman') {
            $this->halaman = 1;
        }
    }

    public function ke(int $halaman): void
    {
        $this->halaman = max(1, $halaman);
    }

    public function render(KatalogPublik $katalog, OpsiMaster $opsi): View
    {
        $hasil = $katalog->daftar([
            'q' => trim($this->q) ?: null,
            'jenis' => $this->jenis ?: null,
            'tingkat' => $this->tingkat ?: null,
            'kategori' => $this->kategori ?: null,
            'prodi' => $this->prodi ?: null,
        ], $this->halaman);

        return view('livewire.publik.katalog-kompetisi', [
            'hasil' => $hasil,
            'opsiJenis' => $opsi->jenisKegiatan(),
            'opsiTingkat' => $opsi->tingkat(),
            'opsiKategori' => $opsi->kategoriLomba($this->jenis ?: null),
            'opsiProdi' => Prodi::query()->where('is_active', true)->orderBy('nama')->pluck('nama', 'id')->all(),
        ]);
    }
}

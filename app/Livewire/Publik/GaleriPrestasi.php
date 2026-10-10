<?php

namespace App\Livewire\Publik;

use App\Models\Prestasi;
use App\Models\Prodi;
use App\Services\GaleriPublik;
use App\Services\OpsiMaster;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Galeri prestasi publik: hanya prestasi terverifikasi yang disetujui dan tidak disembunyikan. */
#[Layout('components.layouts.publik')]
class GaleriPrestasi extends Component
{
    #[Url]
    public ?int $tahun = null;

    #[Url]
    public ?string $tingkat = null;

    #[Url]
    public ?string $prodi = null;

    #[Url]
    public ?string $jenis = null;

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

    public function render(GaleriPublik $galeri, OpsiMaster $opsi): View
    {
        $aktif = GaleriPublik::aktif();

        return view('livewire.publik.galeri-prestasi', [
            'aktif' => $aktif,
            'hasil' => $aktif ? $galeri->daftar([
                'tahun' => $this->tahun ?: null,
                'tingkat' => $this->tingkat ?: null,
                'prodi' => $this->prodi ?: null,
                'jenis' => $this->jenis ?: null,
            ], $this->halaman) : ['items' => [], 'total' => 0, 'halaman' => 1, 'terakhir' => 1],
            'opsiTahun' => $aktif ? Prestasi::query()->tampilDiGaleri()->distinct()->orderByDesc('tahun')->pluck('tahun', 'tahun')->all() : [],
            'opsiTingkat' => $opsi->tingkat(),
            'opsiJenis' => $opsi->jenisKegiatan(),
            'opsiProdi' => Prodi::query()->where('is_active', true)->orderBy('nama')->pluck('nama', 'id')->all(),
        ]);
    }
}

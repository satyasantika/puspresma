<?php

namespace App\Filament\Admin\Widgets;

use App\Services\StatistikPrestasi;
use App\Support\FilterDasbor;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RingkasanPrestasi extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected ?string $description = 'Satu prestasi tim lintas prodi dihitung di setiap prodi anggota; total fakultas menghitung prestasi unik.';

    /** @return array<Stat> */
    protected function getStats(): array
    {
        $r = $this->statistik()->ringkasan();

        return [
            Stat::make('Prestasi terverifikasi', $r['total_prestasi']),
            Stat::make('Mahasiswa berprestasi', $r['total_mahasiswa']),
            Stat::make('Internasional', $r['internasional']),
            Stat::make('Nasional', $r['nasional']),
            Stat::make('Persentase juara', $r['persen_juara'].'%'),
        ];
    }

    public function statistik(): StatistikPrestasi
    {
        return StatistikPrestasi::untuk(
            FilterDasbor::tahun($this->pageFilters['tahun'] ?? null),
            FilterDasbor::prodiEfektif(auth()->user(), $this->pageFilters['prodi_id'] ?? null),
        );
    }
}

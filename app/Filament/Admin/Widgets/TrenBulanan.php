<?php

namespace App\Filament\Admin\Widgets;

use App\Services\StatistikPrestasi;
use App\Support\FilterDasbor;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class TrenBulanan extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;

    protected ?string $heading = 'Tren bulanan';

    protected function getType(): string
    {
        return 'line';
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $data = StatistikPrestasi::untuk(
            FilterDasbor::tahun($this->pageFilters['tahun'] ?? null),
            FilterDasbor::prodiEfektif(auth()->user(), $this->pageFilters['prodi_id'] ?? null),
        )->perBulan();

        return [
            'datasets' => [['label' => 'Prestasi', 'data' => array_values($data)]],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        ];
    }
}

<?php

namespace App\Filament\Admin\Widgets;

use App\Services\StatistikPrestasi;
use App\Support\FilterDasbor;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class GrafikPerTingkat extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Prestasi per tingkat';

    protected function getType(): string
    {
        return 'bar';
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $data = StatistikPrestasi::untuk(
            FilterDasbor::tahun($this->pageFilters['tahun'] ?? null),
            FilterDasbor::prodiEfektif(auth()->user(), $this->pageFilters['prodi_id'] ?? null),
        )->perTingkat();

        return [
            'datasets' => [['label' => 'Prestasi', 'data' => array_values($data)]],
            'labels' => array_keys($data),
        ];
    }
}

<?php

namespace App\Filament\Admin\Widgets;

use App\Services\StatistikPrestasi;
use App\Support\FilterDasbor;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class GrafikPerProdi extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Prestasi per prodi (prodi anggota)';

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
        )->perProdi();

        return [
            'datasets' => [['label' => 'Prestasi', 'data' => array_values($data)]],
            'labels' => array_keys($data),
        ];
    }

    protected function getOptions(): array
    {
        return ['indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false]]];
    }
}

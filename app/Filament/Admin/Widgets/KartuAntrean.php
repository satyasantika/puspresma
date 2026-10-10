<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Prestasi;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Kartu antrean verifikasi (khusus pemverifikasi: admin-fakultas dan admin-prodi). */
class KartuAntrean extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $heading = 'Antrean verifikasi';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->canAny(['prestasi.verifikasi', 'prestasi.verifikasi-prodi']);
    }

    /** @return array<Stat> */
    protected function getStats(): array
    {
        $dasar = Prestasi::query()->when(
            ! auth()->user()?->can('prestasi.lihat-semua'),
            fn ($q) => $q->melibatkanProdi((string) auth()->user()?->prodi_id),
        );

        return [
            Stat::make('Diajukan', (clone $dasar)->where('status', 'diajukan')->count()),
            Stat::make('Lolos tahap prodi', (clone $dasar)->where('status', 'lolos_prodi')->count()),
            Stat::make('Perlu perbaikan', (clone $dasar)->where('status', 'perlu_perbaikan')->count()),
        ];
    }
}

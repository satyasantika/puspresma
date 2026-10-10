<?php

namespace App\Filament\Admin\Pages;

use App\Models\Prestasi;
use App\Models\Prodi;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as DasborBawaan;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/** Dasbor statistik dengan filter tahun dan prodi (admin-prodi dikunci ke prodinya). */
class Dashboard extends DasborBawaan
{
    use HasFiltersForm;

    public function getSubheading(): ?string
    {
        return 'Satu prestasi tim lintas prodi dihitung di setiap prodi anggota; total fakultas menghitung prestasi unik.';
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Filter')->columns(2)->columnSpanFull()->schema([
                Select::make('tahun')->label('Tahun')->placeholder('Semua tahun')
                    ->options(fn () => Prestasi::query()->distinct()->orderByDesc('tahun')->pluck('tahun', 'tahun')->all()),
                Select::make('prodi_id')->label('Prodi')->placeholder('Semua prodi')
                    ->options(fn () => Prodi::query()->orderBy('nama')->pluck('nama', 'id')->all())
                    ->visible(fn (): bool => (bool) auth()->user()?->can('prestasi.lihat-semua')),
            ]),
        ]);
    }
}

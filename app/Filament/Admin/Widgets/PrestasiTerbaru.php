<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Prestasi;
use App\Support\FilterDasbor;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PrestasiTerbaru extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = '10 prestasi terverifikasi terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $prodi = FilterDasbor::prodiEfektif(auth()->user(), $this->pageFilters['prodi_id'] ?? null);
                $tahun = FilterDasbor::tahun($this->pageFilters['tahun'] ?? null);

                return Prestasi::query()->terverifikasi()->with(['tingkat', 'capaian', 'pengaju'])
                    ->when($tahun, fn (Builder $q) => $q->where('tahun', $tahun))
                    ->when($prodi, fn (Builder $q) => $q->melibatkanProdi($prodi))
                    ->orderByDesc('diverifikasi_at')->limit(10);
            })
            ->paginated(false)
            ->columns([
                TextColumn::make('nama_kegiatan')->label('Kegiatan')->limit(50),
                TextColumn::make('pengaju.nama')->label('Pengaju'),
                TextColumn::make('tingkat.nama')->label('Tingkat'),
                TextColumn::make('capaian.nama')->label('Capaian'),
                TextColumn::make('tanggal_selesai')->label('Selesai')->date('d M Y'),
            ]);
    }
}

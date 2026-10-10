<?php

namespace App\Filament\Admin\Resources;

use App\Enums\StatusBerkasEkspor;
use App\Filament\Admin\Resources\BerkasEksporResource\Pages\ListBerkasEkspor;
use App\Models\BerkasEkspor;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Daftar ekspor besar milik pengguna sendiri (read-only; berkas dihapus ≤ 24 jam). */
class BerkasEksporResource extends Resource
{
    protected static ?string $model = BerkasEkspor::class;

    protected static ?string $slug = 'berkas-ekspor';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $modelLabel = 'berkas ekspor';

    protected static ?string $pluralModelLabel = 'Berkas Ekspor';

    protected static ?string $navigationLabel = 'Berkas Ekspor';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('user_id', auth()->id()))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Diminta')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('jenis')->label('Laporan'),
                TextColumn::make('format')->label('Format')->badge(),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (?StatusBerkasEkspor $state) => $state?->label())->color(fn (?StatusBerkasEkspor $state) => $state?->warna()),
                TextColumn::make('jumlah_baris')->label('Baris')->placeholder('—'),
                TextColumn::make('sisa')->label('Sisa waktu')
                    ->state(fn (BerkasEkspor $record): string => $record->status === StatusBerkasEkspor::Siap && ! $record->kedaluwarsa()
                        ? $record->kedaluwarsa_at->diffForHumans(['parts' => 2, 'syntax' => CarbonInterface::DIFF_ABSOLUTE]) : '—'),
            ])
            ->recordActions([
                Action::make('unduh')->label('Unduh')->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (BerkasEkspor $record): bool => $record->status === StatusBerkasEkspor::Siap && ! $record->kedaluwarsa())
                    ->url(fn (BerkasEkspor $record): string => route('ekspor.unduh', $record)),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListBerkasEkspor::route('/')];
    }
}

<?php

namespace App\Filament\Portal\Resources;

use App\Enums\StatusSurat;
use App\Filament\Portal\Resources\SuratSayaResource\Pages\ListSuratSaya;
use App\Models\SuratKeteranganPrestasi;
use App\Services\CetakSuratKeteranganPrestasi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Surat keterangan milik mahasiswa login (lihat dan cetak PDF). */
class SuratSayaResource extends Resource
{
    protected static ?string $model = SuratKeteranganPrestasi::class;

    protected static ?string $slug = 'surat-saya';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?string $modelLabel = 'surat keterangan';

    protected static ?string $pluralModelLabel = 'Surat Keterangan Saya';

    protected static ?string $navigationLabel = 'Surat Keterangan Saya';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('prestasi.buat-milik') && parent::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $mahasiswaId = auth()->user()?->loadMissing('mahasiswa')->mahasiswa?->id;

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $mahasiswaId === null ? $query->whereRaw('1 = 0') : $query->where('mahasiswa_id', $mahasiswaId))
            ->defaultSort('tanggal_terbit', 'desc')
            ->columns([
                TextColumn::make('nomor')->label('Nomor'),
                TextColumn::make('keperluan')->label('Keperluan'),
                TextColumn::make('tanggal_terbit')->label('Terbit')->date('d M Y'),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (?StatusSurat $state) => $state?->label())->color(fn (?StatusSurat $state) => $state?->warna()),
            ])
            ->recordActions([
                Action::make('cetak')->label('Cetak PDF')->icon('heroicon-o-printer')
                    ->visible(fn (SuratKeteranganPrestasi $record): bool => (bool) auth()->user()?->can('cetak', $record))
                    ->action(fn (SuratKeteranganPrestasi $record) => app(CetakSuratKeteranganPrestasi::class)->stream($record)),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListSuratSaya::route('/')];
    }
}

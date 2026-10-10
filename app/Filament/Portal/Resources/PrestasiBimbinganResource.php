<?php

namespace App\Filament\Portal\Resources;

use App\Enums\StatusKonfirmasiPembimbing;
use App\Enums\StatusPrestasi;
use App\Filament\Portal\Resources\PrestasiBimbinganResource\Pages\ListPrestasiBimbingan;
use App\Models\Prestasi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Prestasi bimbingan yang sudah dikonfirmasi dosen login (hanya baca). */
class PrestasiBimbinganResource extends Resource
{
    protected static ?string $model = Prestasi::class;

    protected static ?string $slug = 'prestasi-bimbingan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $modelLabel = 'prestasi bimbingan';

    protected static ?string $pluralModelLabel = 'Prestasi Bimbingan';

    protected static ?string $navigationLabel = 'Prestasi Bimbingan';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('prestasi.lihat-bimbingan') && parent::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => KonfirmasiPembimbingResource::queryDosen(StatusKonfirmasiPembimbing::Dikonfirmasi->value)->with(['pengaju', 'tingkat', 'capaian']))
            ->defaultSort('tanggal_selesai', 'desc')
            ->columns([
                TextColumn::make('nomor_registrasi')->label('No. registrasi')->placeholder('—'),
                TextColumn::make('nama_kegiatan')->label('Kegiatan')->wrap()->limit(60),
                TextColumn::make('pengaju.nama')->label('Mahasiswa'),
                TextColumn::make('tingkat.nama')->label('Tingkat'),
                TextColumn::make('capaian.nama')->label('Capaian'),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (?StatusPrestasi $state) => $state?->label())->color(fn (?StatusPrestasi $state) => $state?->warna()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPrestasiBimbingan::route('/')];
    }
}

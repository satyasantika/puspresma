<?php

namespace App\Filament\Admin\Resources;

use App\Enums\StatusTinjauDuplikat;
use App\Filament\Admin\Resources\KandidatDuplikatResource\Pages\ListKandidatDuplikat;
use App\Filament\Admin\Support\AksiTinjauDuplikat;
use App\Models\KandidatDuplikat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Daftar global kandidat duplikat; admin-prodi hanya yang melibatkan prodinya. */
class KandidatDuplikatResource extends Resource
{
    protected static ?string $model = KandidatDuplikat::class;

    protected static ?string $slug = 'kandidat-duplikat';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?string $modelLabel = 'kandidat duplikat';

    protected static ?string $pluralModelLabel = 'Kandidat Duplikat';

    protected static ?string $navigationLabel = 'Kandidat Duplikat';

    protected static string|UnitEnum|null $navigationGroup = 'Prestasi';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                /** @var Builder<KandidatDuplikat> $query */
                $pengguna = auth()->user();
                if ($pengguna === null || ! $pengguna->can('duplikat.tinjau')) {
                    $prodiId = $pengguna?->prodi_id;
                    $prodiId === null
                        ? $query->whereRaw('1 = 0')
                        : $query->whereHas('prestasi.anggota', fn ($q) => $q->where('prodi_id', $prodiId));
                }

                return $query->with(['prestasi', 'pembanding']);
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('prestasi.nomor_registrasi')->label('Prestasi')->placeholder('—'),
                TextColumn::make('prestasi.nama_kegiatan')->label('Kegiatan')->limit(40),
                TextColumn::make('pembanding.nomor_registrasi')->label('Pembanding')->placeholder('—'),
                TextColumn::make('alasan')->label('Alasan')->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('skor')->label('Skor'),
                TextColumn::make('status_tinjau')->label('Status')->badge()
                    ->formatStateUsing(fn (?StatusTinjauDuplikat $state) => $state?->label())->color(fn (?StatusTinjauDuplikat $state) => $state?->warna()),
            ])
            ->filters([
                SelectFilter::make('status_tinjau')->label('Status')->default(StatusTinjauDuplikat::BelumDitinjau->value)
                    ->options(collect(StatusTinjauDuplikat::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->recordActions([AksiTinjauDuplikat::tinjau()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListKandidatDuplikat::route('/')];
    }
}

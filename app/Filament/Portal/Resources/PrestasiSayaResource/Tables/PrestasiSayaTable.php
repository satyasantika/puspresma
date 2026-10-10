<?php

namespace App\Filament\Portal\Resources\PrestasiSayaResource\Tables;

use App\Enums\StatusPrestasi;
use App\Filament\Portal\Resources\PrestasiSayaResource\Actions\AksiPrestasi;
use App\Models\Prestasi;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PrestasiSayaTable
{
    public static function configure(Table $table): Table
    {
        $mahasiswaId = auth()->user()?->loadMissing('mahasiswa')->mahasiswa?->id;

        return $table
            ->modifyQueryUsing(function (Builder $query) use ($mahasiswaId): Builder {
                /** @var Builder<Prestasi> $query */
                $mahasiswaId === null ? $query->whereRaw('1 = 0') : $query->milikMahasiswa($mahasiswaId);

                return $query->with(['tingkat', 'capaian']);
            })
            ->defaultSort('tanggal_selesai', 'desc')
            ->columns([
                TextColumn::make('nomor_registrasi')->label('No. registrasi')->placeholder('—')->searchable(),
                TextColumn::make('nama_kegiatan')->label('Kegiatan')->searchable()->limit(50)->wrap(),
                TextColumn::make('tingkat.nama')->label('Tingkat'),
                TextColumn::make('capaian.nama')->label('Capaian'),
                TextColumn::make('tanggal_selesai')->label('Selesai')->date('d M Y')->sortable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (?StatusPrestasi $state) => $state?->label())
                    ->color(fn (?StatusPrestasi $state) => $state?->warna()),
                TextColumn::make('peran_saya')->label('Peran saya')
                    ->state(fn (Prestasi $record): string => $record->pengaju_id === $mahasiswaId ? 'Pengaju' : 'Anggota'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')
                    ->options(collect(StatusPrestasi::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                SelectFilter::make('tahun')->label('Tahun')
                    ->options(fn () => Prestasi::query()->distinct()->orderByDesc('tahun')->pluck('tahun', 'tahun')->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                AksiPrestasi::ajukan(),
                AksiPrestasi::tarikKembali(),
                DeleteAction::make(),
            ]);
    }
}

<?php

namespace App\Filament\Admin\Resources\PrestasiResource\Tables;

use App\Enums\StatusPrestasi;
use App\Models\Capaian;
use App\Models\KandidatDuplikat;
use App\Models\Prestasi;
use App\Models\Prodi;
use App\Models\TautanBerkas;
use App\Models\Tingkat;
use App\Services\OpsiMaster;
use App\Services\PeringatanPrestasi;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PrestasiTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                /** @var Builder<Prestasi> $query */
                $pengguna = auth()->user();
                // admin-fakultas/pimpinan/super-admin: semua; admin-prodi: prestasi yang melibatkan prodinya (BR-15).
                if ($pengguna === null || ! $pengguna->can('prestasi.lihat-semua')) {
                    $pengguna?->prodi_id === null ? $query->whereRaw('1 = 0') : $query->melibatkanProdi($pengguna->prodi_id);
                }

                // Penanda peringatan lewat subquery (satu kueri untuk seluruh halaman, tanpa N+1).
                return $query
                    ->select('prestasi.*')
                    ->selectSub(
                        KandidatDuplikat::query()->selectRaw('1')->whereColumn('kandidat_duplikat.prestasi_id', 'prestasi.id')
                            ->where('status_tinjau', 'belum_ditinjau')->limit(1),
                        'ada_duplikat',
                    )
                    ->selectSub(
                        TautanBerkas::query()->selectRaw('1')->whereColumn('tautan_berkas.pemilik_id', 'prestasi.id')
                            ->where('tautan_berkas.pemilik_type', 'prestasi')->where('jenis', '!=', 'poster')
                            ->where('status_cek', 'tidak_dapat_diakses')->limit(1),
                        'tautan_bermasalah',
                    )
                    ->with(['pengaju', 'prodi', 'tingkat', 'capaian']);
            })
            ->defaultSort('diajukan_at', 'desc')
            ->columns([
                TextColumn::make('nomor_registrasi')->label('No. registrasi')->placeholder('—')->searchable(false),
                TextColumn::make('nama_kegiatan')->label('Kegiatan')->limit(45)->wrap()
                    ->searchable(query: fn (Builder $query, string $search): Builder => self::cari($query, $search)),
                TextColumn::make('pengaju.nama')->label('Pengaju')
                    ->formatStateUsing(fn (?string $state, Prestasi $record): string => "{$record->pengaju->npm} — {$state}"),
                TextColumn::make('prodi.nama')->label('Prodi')->toggleable(),
                TextColumn::make('tingkat.nama')->label('Tingkat'),
                TextColumn::make('capaian.nama')->label('Capaian'),
                IconColumn::make('bentuk')->label('Bentuk')
                    ->icon(fn ($state) => $state?->value === 'kelompok' ? 'heroicon-o-user-group' : 'heroicon-o-user')
                    ->tooltip(fn (Prestasi $record): string => $record->bentuk->label()),
                TextColumn::make('tanggal_selesai')->label('Selesai')->date('d M Y')->sortable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (?StatusPrestasi $state) => $state?->label())
                    ->color(fn (?StatusPrestasi $state) => $state?->warna()),
                IconColumn::make('peringatan')->label('')
                    ->state(fn (Prestasi $record): bool => PeringatanPrestasi::untuk($record) !== [])
                    ->icon(fn (bool $state): ?string => $state ? 'heroicon-o-exclamation-triangle' : null)->color('warning')
                    ->tooltip(fn (Prestasi $record): ?string => implode(' ', PeringatanPrestasi::untuk($record)) ?: null),
            ])
            ->filters([
                SelectFilter::make('prodi_id')->label('Prodi')->options(fn () => Prodi::orderBy('nama')->pluck('nama', 'id')->all()),
                SelectFilter::make('tahun')->label('Tahun')->options(fn () => Prestasi::query()->distinct()->orderByDesc('tahun')->pluck('tahun', 'tahun')->all()),
                SelectFilter::make('jenis_kegiatan_id')->label('Jenis')->options(fn () => app(OpsiMaster::class)->jenisKegiatan()),
                SelectFilter::make('tingkat_id')->label('Tingkat')->options(fn () => Tingkat::orderBy('urutan')->pluck('nama', 'id')->all()),
                SelectFilter::make('capaian_id')->label('Capaian')->options(fn () => Capaian::orderBy('nama')->pluck('nama', 'id')->all()),
                SelectFilter::make('status')->label('Status')->options(collect(StatusPrestasi::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Filter::make('ada_duplikat')->label('Ada duplikat')->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereHas('kandidatDuplikat', fn ($q) => $q->where('status_tinjau', 'belum_ditinjau'))),
            ])
            ->recordActions([ViewAction::make()->label('Periksa')]);
    }

    /**
     * Pencarian: FULLTEXT nama kegiatan/penyelenggara, atau NPM/nama anggota.
     *
     * @param  Builder<Prestasi>  $query
     * @return Builder<Prestasi>
     */
    private static function cari(Builder $query, string $kata): Builder
    {
        return $query->where(function (Builder $q) use ($kata): void {
            $q->whereFullText(['nama_kegiatan', 'penyelenggara'], $kata)
                ->orWhere('nama_kegiatan', 'like', "%{$kata}%")
                ->orWhereHas('anggota.mahasiswa', fn (Builder $m) => $m->where('npm', 'like', "{$kata}%")->orWhere('nama', 'like', "%{$kata}%"));
        });
    }
}

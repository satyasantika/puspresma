<?php

namespace App\Filament\Admin\Resources\PrestasiResource\Pages;

use App\Enums\StatusPrestasi;
use App\Filament\Admin\Resources\PrestasiResource;
use App\Services\Pengaturan;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ListPrestasi extends ListRecords
{
    protected static string $resource = PrestasiResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Catat atas nama mahasiswa')];
    }

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        return [
            'antrean' => Tab::make('Antrean')
                ->modifyQueryUsing(fn (Builder $query) => $this->antrean($query))
                ->badge(fn () => $this->antrean(PrestasiResource::getEloquentQuery())->count() ?: null),
            'perlu_perbaikan' => Tab::make('Perlu Perbaikan')->modifyQueryUsing(fn (Builder $query) => $query->where('status', StatusPrestasi::PerluPerbaikan->value)),
            'terverifikasi' => Tab::make('Terverifikasi')->modifyQueryUsing(fn (Builder $query) => $query->where('status', StatusPrestasi::Terverifikasi->value)),
            'ditolak' => Tab::make('Ditolak')->modifyQueryUsing(fn (Builder $query) => $query->where('status', StatusPrestasi::Ditolak->value)),
            'semua' => Tab::make('Semua'),
        ];
    }

    /**
     * Antrean: diajukan + lolos_prodi; admin-prodi (tahap prodi aktif) hanya melihat diajukan prodi pengajunya.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    private function antrean(Builder $query): Builder
    {
        $pengguna = auth()->user();

        if ($pengguna !== null && ! $pengguna->can('prestasi.verifikasi') && $pengguna->can('prestasi.verifikasi-prodi')) {
            return $query->where('status', StatusPrestasi::Diajukan->value)
                ->where('prodi_id', $pengguna->prodi_id)
                ->when(! Pengaturan::ambil('verifikasi_prodi_aktif', false), fn (Builder $q) => $q->whereRaw('1 = 0'));
        }

        return $query->whereIn('status', [StatusPrestasi::Diajukan->value, StatusPrestasi::LolosProdi->value]);
    }
}

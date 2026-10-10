<?php

namespace App\Filament\Portal\Resources\PrestasiSayaResource\Pages;

use App\Filament\Portal\Resources\PrestasiSayaResource;
use App\Filament\Portal\Resources\PrestasiSayaResource\Actions\AksiPrestasi;
use App\Filament\Shared\Concerns\SimpanRelasiPrestasi;
use App\Models\Prestasi;
use App\Models\RiwayatStatusPrestasi;
use App\Models\TautanBerkas;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class ViewPrestasiSaya extends ViewRecord
{
    use SimpanRelasiPrestasi;

    protected static string $resource = PrestasiSayaResource::class;

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFormContentComponent(),
            View::make('filament.portal.prestasi.tautan-bukti')->viewData(fn (): array => [
                // Otorisasi di PHP (Policy), bukan di Blade: tautan hanya dirender bagi yang berhak.
                'tautan' => $this->tautanYangBolehDilihat(),
            ]),
            View::make('filament.portal.prestasi.riwayat-status')->viewData(fn (): array => [
                'riwayat' => $this->riwayatStatus(),
            ]),
        ]);
    }

    /** @return EloquentCollection<int, RiwayatStatusPrestasi> */
    private function riwayatStatus(): EloquentCollection
    {
        /** @var Prestasi $prestasi */
        $prestasi = $this->getRecord();

        return $prestasi->riwayatStatus()->with('user')->get();
    }

    /** @return Collection<int, TautanBerkas> */
    private function tautanYangBolehDilihat(): Collection
    {
        /** @var Prestasi $prestasi */
        $prestasi = $this->getRecord();

        return $prestasi->tautanBukti()->get()->filter(fn ($t) => Gate::allows('view', $t))->values();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Prestasi $prestasi */
        $prestasi = $this->getRecord();

        return $this->muatRelasi($prestasi, $data);
    }

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), AksiPrestasi::ajukan(), AksiPrestasi::tarikKembali()];
    }
}

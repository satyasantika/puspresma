<?php

namespace App\Filament\Admin\Pages;

use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Services\PeringkatPoin as LayananPeringkat;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class PeringkatPoin extends Page
{
    protected static ?string $slug = 'peringkat-poin';

    protected static ?string $title = 'Peringkat Poin';

    protected static ?string $navigationLabel = 'Peringkat Poin';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Poin';

    protected string $view = 'filament.admin.pages.peringkat-poin';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('laporan.lihat');
    }

    public function mount(): void
    {
        $this->form->fill([]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->columns(4)->components([
            DatePicker::make('dari')->label('Dari tanggal selesai')->live()->native(false),
            DatePicker::make('sampai')->label('Sampai tanggal selesai')->live()->native(false),
            Select::make('prodi_id')->label('Prodi')->live()->placeholder('Semua prodi')
                ->options(fn () => Prodi::query()->orderBy('nama')->pluck('nama', 'id')->all())
                ->visible(fn (): bool => (bool) auth()->user()?->can('prestasi.lihat-semua')),
            TextInput::make('angkatan')->label('Angkatan')->numeric()->integer()->live(debounce: 500),
        ]);
    }

    /** @return Collection<int, array{peringkat: int, mahasiswa: Mahasiswa, jumlah_prestasi: int, total_poin: string}> */
    #[Computed]
    public function peringkat(): Collection
    {
        return app(LayananPeringkat::class)->hitung(auth()->user(), [
            'dari' => $this->data['dari'] ?? null,
            'sampai' => $this->data['sampai'] ?? null,
            'prodi_id' => $this->data['prodi_id'] ?? null,
            'angkatan' => filled($this->data['angkatan'] ?? null) ? (int) $this->data['angkatan'] : null,
        ]);
    }
}

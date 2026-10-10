<?php

namespace App\Filament\Admin\Pages;

use App\Actions\Simkatmawa\TandaiDilaporkanSimkatmawa;
use App\Models\BerkasEkspor;
use App\Models\Prestasi;
use App\Services\Ekspor\EksporService;
use App\Services\Ekspor\RegistriLaporan;
use App\Services\Ekspor\SimkatmawaLaporan;
use App\Services\OpsiMaster;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class EksporSimkatmawa extends Page
{
    protected static ?string $slug = 'ekspor-simkatmawa';

    protected static ?string $title = 'Ekspor SIMKATMAWA';

    protected static ?string $navigationLabel = 'Ekspor SIMKATMAWA';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpOnSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected string $view = 'filament.admin.pages.ekspor-simkatmawa';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('simkatmawa.ekspor');
    }

    public function mount(): void
    {
        $this->form->fill(['belum_dilaporkan' => true]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->columns(3)->components([
            Select::make('tahun')->label('Tahun')->placeholder('Semua')->live()
                ->options(fn () => Prestasi::query()->distinct()->orderByDesc('tahun')->pluck('tahun', 'tahun')->all()),
            Select::make('jenis_kegiatan_id')->label('Jenis')->placeholder('Semua')->live()->options(fn () => app(OpsiMaster::class)->jenisKegiatan()),
            Toggle::make('belum_dilaporkan')->label('Hanya yang belum dilaporkan')->live(),
        ]);
    }

    /** @return array<string, mixed> */
    private function filter(): array
    {
        return ['tahun' => $this->data['tahun'] ?? null, 'jenis_kegiatan_id' => $this->data['jenis_kegiatan_id'] ?? null, 'belum_dilaporkan' => (bool) ($this->data['belum_dilaporkan'] ?? true)];
    }

    /** @return array<int, array{nomor: ?string, kegiatan: string, tingkat: string, capaian: string, tautan: list<array{id: string, jenis: string}>}> */
    #[Computed]
    public function pratinjau(): array
    {
        return app(SimkatmawaLaporan::class)->query($this->filter(), auth()->user())->with(['tingkat', 'capaian', 'tautanBukti'])->limit(20)->get()
            ->map(fn (Prestasi $p): array => [
                'nomor' => $p->nomor_registrasi, 'kegiatan' => $p->nama_kegiatan, 'tingkat' => $p->tingkat->nama, 'capaian' => $p->capaian->nama,
                'tautan' => $p->tautanBukti->map(fn ($t) => ['id' => $t->id, 'jenis' => $t->jenis->label()])->all(),
            ])->all();
    }

    #[Computed]
    public function totalPrestasi(): int
    {
        return app(SimkatmawaLaporan::class)->query($this->filter(), auth()->user())->count();
    }

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('xlsx')->label('Ekspor XLSX')->action(fn () => $this->ekspor('xlsx')),
            Action::make('csv')->label('Ekspor CSV')->color('gray')->action(fn () => $this->ekspor('csv')),
            Action::make('tandai')->label('Tandai semua dilaporkan')->color('success')->requiresConfirmation()
                ->modalDescription('Semua prestasi pada filter ini ditandai sudah dilaporkan ke SIMKATMAWA.')
                ->action(function (): void {
                    $jumlah = app(TandaiDilaporkanSimkatmawa::class)->jalankan(app(SimkatmawaLaporan::class)->query($this->filter(), auth()->user())->get(), auth()->user());
                    Notification::make()->title("{$jumlah} prestasi ditandai dilaporkan")->success()->send();
                }),
        ];
    }

    private function ekspor(string $format): mixed
    {
        try {
            $hasil = app(EksporService::class)->minta(RegistriLaporan::buat('simkatmawa'), $format, $this->filter(), auth()->user());
        } catch (ValidationException|AuthorizationException $e) {
            Notification::make()->title('Tidak dapat diekspor')->body($e instanceof ValidationException ? collect($e->errors())->flatten()->implode("\n") : $e->getMessage())->danger()->send();

            return null;
        }

        if ($hasil instanceof BerkasEkspor) {
            Notification::make()->title('Ekspor besar masuk antrean')->body('Berkas muncul di menu Berkas Ekspor (24 jam).')->success()->send();

            return null;
        }

        return $hasil;
    }
}

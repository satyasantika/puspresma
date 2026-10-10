<?php

namespace App\Filament\Admin\Pages;

use App\Models\BerkasEkspor;
use App\Models\Capaian;
use App\Models\Prestasi;
use App\Models\Prodi;
use App\Models\Tingkat;
use App\Services\Ekspor\EksporService;
use App\Services\Ekspor\RegistriLaporan;
use App\Services\OpsiMaster;
use App\Support\FilterDasbor;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * Laporan LAP-01/02/06/09. Ekspor kecil di-stream, ekspor besar lewat antrean (lihat EksporService).
 *
 * @property-read Schema $form
 */
class Laporan extends Page
{
    protected static ?string $slug = 'laporan';

    protected static ?string $title = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected string $view = 'filament.admin.pages.laporan';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('laporan.ekspor');
    }

    public function mount(): void
    {
        $this->form->fill(['tahun' => null]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Filter')->columns(3)->schema([
                Select::make('tahun')->label('Tahun')->placeholder('Semua')->options(fn () => Prestasi::query()->distinct()->orderByDesc('tahun')->pluck('tahun', 'tahun')->all()),
                DatePicker::make('dari')->label('Selesai dari')->native(false),
                DatePicker::make('sampai')->label('Selesai sampai')->native(false),
                Select::make('prodi_id')->label('Prodi')->placeholder('Semua')->options(fn () => Prodi::query()->orderBy('nama')->pluck('nama', 'id')->all())
                    ->visible(fn (): bool => (bool) auth()->user()?->can('prestasi.lihat-semua')),
                Select::make('jenis_kegiatan_id')->label('Jenis')->placeholder('Semua')->options(fn () => app(OpsiMaster::class)->jenisKegiatan()),
                Select::make('bidang')->label('Bidang')->placeholder('Semua')->options(['akademik' => 'Akademik', 'non_akademik' => 'Non-akademik']),
                Select::make('tingkat_id')->label('Tingkat')->placeholder('Semua')->options(fn () => Tingkat::orderBy('urutan')->pluck('nama', 'id')->all()),
                Select::make('capaian_id')->label('Capaian')->placeholder('Semua')->options(fn () => Capaian::orderBy('nama')->pluck('nama', 'id')->all()),
                Select::make('status')->label('Status')->options([
                    'terverifikasi' => 'Terverifikasi', 'diajukan' => 'Diajukan', 'perlu_perbaikan' => 'Perlu perbaikan', 'ditolak' => 'Ditolak', 'semua' => 'Semua status',
                ])->default('terverifikasi'),
            ]),
        ]);
    }

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        $tombol = [
            ['rekap_prestasi', 'xlsx', 'LAP-01 Rekap prestasi (XLSX)'], ['rekap_prestasi', 'pdf', 'LAP-01 Rekap prestasi (PDF)'],
            ['rekap_anggota', 'xlsx', 'LAP-02 Rekap per anggota (XLSX)'],
            ['peringkat_poin', 'xlsx', 'LAP-06 Peringkat poin (XLSX)'], ['peringkat_poin', 'pdf', 'LAP-06 Peringkat poin (PDF)'],
            ['katalog', 'xlsx', 'LAP-09 Rekap katalog (XLSX)'],
        ];

        return array_map(fn (array $t): Action => Action::make("{$t[0]}_{$t[1]}")->label($t[2])->color('gray')
            ->visible(fn (): bool => (bool) auth()->user()?->can(RegistriLaporan::buat($t[0])->izin()))
            ->action(fn () => $this->ekspor($t[0], $t[1])), $tombol);
    }

    private function ekspor(string $jenis, string $format): mixed
    {
        $pengguna = auth()->user();
        $filter = collect($this->form->getState())->filter(fn ($v) => filled($v))->all();
        $filter['prodi_id'] = FilterDasbor::prodiEfektif($pengguna, $filter['prodi_id'] ?? null);
        $filter = array_filter($filter, fn ($v) => $v !== null);

        try {
            $hasil = app(EksporService::class)->minta(RegistriLaporan::buat($jenis), $format, $filter, $pengguna);
        } catch (ValidationException|AuthorizationException $e) {
            Notification::make()->title('Tidak dapat diekspor')->body($e instanceof ValidationException ? collect($e->errors())->flatten()->implode("\n") : $e->getMessage())->danger()->send();

            return null;
        }

        if ($hasil instanceof BerkasEkspor) {
            Notification::make()->title('Ekspor besar masuk antrean')->body('Anda akan diberi tahu saat berkas siap (tersedia 24 jam).')->success()->send();

            return null;
        }

        return $hasil;
    }
}

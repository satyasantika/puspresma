<?php

namespace App\Filament\Admin\Pages;

use App\Actions\Poin\HitungUlangPoinMassal;
use App\Actions\Poin\SimpanMatriksBobot;
use App\Models\BobotPoin;
use App\Services\OpsiMaster;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;
use UnitEnum;

/** @property-read Schema $form */
class MatriksBobotPoin extends Page
{
    protected static ?string $slug = 'matriks-bobot-poin';

    protected static ?string $title = 'Matriks Bobot Poin';

    protected static ?string $navigationLabel = 'Matriks Bobot Poin';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|UnitEnum|null $navigationGroup = 'Poin';

    protected string $view = 'filament.admin.pages.matriks-bobot-poin';

    /** @var array<string, mixed> */
    public ?array $data = [];

    /** @var array<string, array<string, string>> */
    public array $matriks = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('poin.kelola');
    }

    public function mount(): void
    {
        $this->form->fill(['berlaku_mulai' => now()->toDateString()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->columns(3)->components([
            Select::make('jenis_kegiatan_id')->label('Jenis kegiatan')
                ->options(fn () => app(OpsiMaster::class)->jenisKegiatan())->required()->live()
                ->afterStateUpdated(fn () => $this->muatMatriks()),
            DatePicker::make('berlaku_mulai')->label('Berlaku mulai')->required(),
            TextInput::make('dasar_penetapan')->label('Dasar penetapan (mis. nomor SK Dekan)')->required()->maxLength(255),
        ]);
    }

    /**
     * Capaian aktif jenis terpilih.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function capaian(): array
    {
        $jenis = $this->data['jenis_kegiatan_id'] ?? null;

        return $jenis ? app(OpsiMaster::class)->capaian($jenis) : [];
    }

    /** @return array<string, string> */
    #[Computed]
    public function tingkat(): array
    {
        return app(OpsiMaster::class)->tingkat();
    }

    /** Isi grid dengan bobot yang sedang berlaku (periode terbuka terbaru) sebagai titik awal. */
    public function muatMatriks(): void
    {
        $this->matriks = [];
        $jenis = $this->data['jenis_kegiatan_id'] ?? null;
        if (! $jenis) {
            return;
        }

        foreach (BobotPoin::where('jenis_kegiatan_id', $jenis)->whereNull('berlaku_sampai')->get() as $bobot) {
            $this->matriks[$bobot->capaian_id][$bobot->tingkat_id] = (string) $bobot->poin;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('hitungUlang')->label('Hitung ulang poin')->icon('heroicon-o-arrow-path')->color('warning')
                ->visible(fn (): bool => (bool) auth()->user()?->can('poin.hitung-ulang'))
                ->requiresConfirmation()
                ->modalDescription('Poin seluruh prestasi terverifikasi dihitung ulang sesuai bobot dan mode saat ini. Tindakan tercatat di audit.')
                ->action(function (): void {
                    $jumlah = app(HitungUlangPoinMassal::class)->jalankan(auth()->user());
                    Notification::make()->title("{$jumlah} prestasi dihitung ulang")->success()->send();
                }),
        ];
    }

    public function simpan(): void
    {
        $data = $this->form->getState();

        $jumlah = app(SimpanMatriksBobot::class)->jalankan(
            $data['jenis_kegiatan_id'],
            CarbonImmutable::parse($data['berlaku_mulai']),
            $data['dasar_penetapan'],
            $this->matriks,
            auth()->user(),
        );

        $this->muatMatriks();
        Notification::make()->title("{$jumlah} bobot disimpan sebagai periode baru")->success()->send();
    }
}

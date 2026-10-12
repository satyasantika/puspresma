<?php

namespace App\Filament\Admin\Pages;

use App\Actions\Pengguna\ImporPenggunaDariSalinan;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ImporPengguna extends Page
{
    protected static ?string $slug = 'impor-pengguna';

    protected static ?string $title = 'Impor Pengguna (salin-tempel)';

    protected static ?string $navigationLabel = 'Impor Pengguna';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected string $view = 'filament.admin.pages.impor-pengguna';

    /** @var array<string, mixed> */
    public ?array $data = [];

    /** @var list<array<string, mixed>> */
    public array $pratinjau = [];

    /** @var array{dibuat: int, diubah: int, gagal: list<array{no: int, pesan: string}>}|null */
    public ?array $hasil = null;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('pengguna.kelola');
    }

    public function mount(): void
    {
        $this->form->fill(['wajib_ganti_sandi' => true]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Textarea::make('teks')->label('Data pengguna')->rows(12)->required()
                ->placeholder("Nama\tSurel\tPeran\tKode prodi\tNPM\tNIDN\tNIP\tNomor WA\tSandi")
                ->helperText('Tempel dari Excel/Google Sheets (tab), atau CSV/titik koma. Baris judul opsional. Urutan kolom: nama, surel, peran, kode prodi, NPM, NIDN, NIP, nomor WA, sandi (hanya nama, surel, dan peran yang wajib). Beberapa peran dipisah "|". Maksimal '.ImporPenggunaDariSalinan::MAKS_BARIS.' baris.'),
            TextInput::make('sandi_awal')->label('Sandi awal (opsional)')->password()->revealable()
                ->rule(Password::min(12)->letters()->numbers())
                ->helperText('Dipakai untuk baris yang tidak memuat sandi. Kosong = pengguna baru menerima tautan atur kata sandi lewat surel.'),
            Toggle::make('wajib_ganti_sandi')->label('Wajibkan ganti kata sandi saat masuk pertama')
                ->helperText('Berlaku untuk akun yang diberi sandi lewat impor ini.'),
        ])->columns(1);
    }

    public function periksa(): void
    {
        $this->hasil = null;
        $teks = (string) ($this->form->getState()['teks'] ?? '');

        try {
            $this->pratinjau = app(ImporPenggunaDariSalinan::class)->periksa($teks);
        } catch (ValidationException $e) {
            $this->pratinjau = [];
            Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();
        }
    }

    public function impor(): void
    {
        $data = $this->form->getState();

        try {
            $this->hasil = app(ImporPenggunaDariSalinan::class)->jalankan((string) $data['teks'], [
                'sandi_awal' => $data['sandi_awal'] ?? null,
                'wajib_ganti_sandi' => (bool) ($data['wajib_ganti_sandi'] ?? true),
            ], auth()->user());
        } catch (ValidationException $e) {
            Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();

            return;
        }

        $this->pratinjau = [];
        Notification::make()
            ->title("Impor selesai: {$this->hasil['dibuat']} dibuat, {$this->hasil['diubah']} diperbarui, ".count($this->hasil['gagal']).' gagal')
            ->color($this->hasil['gagal'] === [] ? 'success' : 'warning')->send();
    }
}

<?php

namespace App\Filament\Admin\Pages;

use App\Services\Pengaturan;
use BackedEnum;
use Database\Seeders\PengaturanSeeder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** @property-read Schema $form */
class PengaturanSistem extends Page
{
    protected static ?string $slug = 'pengaturan';

    protected static ?string $title = 'Pengaturan Sistem';

    protected static ?string $navigationLabel = 'Pengaturan Sistem';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected string $view = 'filament.admin.pages.pengaturan-sistem';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('pengaturan.kelola');
    }

    public function mount(): void
    {
        $nilai = [];
        foreach (PengaturanSeeder::daftar() as $baris) {
            $nilai[$baris['kunci']] = Pengaturan::ambil($baris['kunci']);
        }

        $this->form->fill($nilai);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Verifikasi')->schema([
                Toggle::make('verifikasi_prodi_aktif')->label('Tahap verifikasi prodi aktif')
                    ->helperText('Bila aktif, prestasi diverifikasi admin prodi lebih dahulu sebelum fakultas.'),
            ]),
            Section::make('Poin')->columns(2)->schema([
                Toggle::make('poin_aktif')->label('Poin prestasi aktif'),
                Select::make('mode_poin_tim')->label('Mode poin tim')
                    ->options(['penuh' => 'Penuh (tiap anggota)', 'dibagi' => 'Dibagi rata'])->required(),
            ]),
            Section::make('Galeri')->schema([
                Toggle::make('galeri_aktif')->label('Galeri publik aktif'),
                Textarea::make('teks_persetujuan_publikasi')->label('Teks persetujuan publikasi')->rows(4)->required()
                    ->helperText('Draf; perlu ditinjau bagian hukum sebelum dipakai.'),
            ]),
            Section::make('Duplikat')->columns(2)->schema([
                TextInput::make('ambang_kemiripan_duplikat')->label('Ambang kemiripan (0,50–1,00)')
                    ->numeric()->minValue(0.5)->maxValue(1)->step(0.01)->required(),
                TextInput::make('rentang_hari_duplikat')->label('Rentang hari (0–60)')
                    ->numeric()->integer()->minValue(0)->maxValue(60)->required(),
            ]),
            Section::make('Validasi')->columns(2)->schema([
                TextInput::make('ambang_negara_internasional')->label('Ambang jumlah negara (internasional)')
                    ->numeric()->integer()->minValue(0)->helperText('Perlu verifikasi; hanya peringatan.'),
                TextInput::make('ambang_pt_nasional')->label('Ambang jumlah perguruan tinggi (nasional)')
                    ->numeric()->integer()->minValue(0)->helperText('Kosong = tanpa ambang (perlu verifikasi).'),
                TextInput::make('batas_klaim_bulan')->label('Batas klaim (bulan)')
                    ->numeric()->integer()->minValue(1)->required(),
                TextInput::make('awal_tahun_akademik_bulan')->label('Bulan awal tahun akademik (1–12)')
                    ->numeric()->integer()->minValue(1)->maxValue(12)->required()->helperText('Perlu verifikasi.'),
            ]),
            Section::make('WhatsApp')->schema([
                Toggle::make('wa_aktif')->label('Notifikasi WhatsApp aktif'),
            ]),
        ]);
    }

    public function simpan(): void
    {
        abort_unless(static::canAccess(), 403);

        foreach ($this->form->getState() as $kunci => $nilai) {
            if (! in_array($kunci, array_column(PengaturanSeeder::daftar(), 'kunci'), true)) {
                continue;
            }

            if ($nilai !== Pengaturan::ambil($kunci)) {
                Pengaturan::setel($kunci, $nilai);
            }
        }

        Notification::make()->title('Pengaturan disimpan')->success()->send();
    }
}

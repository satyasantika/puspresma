<?php

namespace App\Filament\Portal\Resources\PrestasiSayaResource\Schemas;

use App\Actions\Prestasi\SimpanPrestasi;
use App\Enums\BentukPartisipasi;
use App\Enums\BidangPrestasi;
use App\Enums\ModaKegiatan;
use App\Services\OpsiMaster;
use App\Services\Pengaturan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class PrestasiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                self::langkahKegiatan(),
                self::langkahCapaian(),
                self::langkahTim(),
                self::langkahBukti(),
            ])->skippable()->columnSpanFull(),
        ]);
    }

    private static function langkahKegiatan(): Step
    {
        return Step::make('Kegiatan')->columns(2)->schema([
            Select::make('jenis_kegiatan_id')->label('Jenis kegiatan')
                ->options(fn () => app(OpsiMaster::class)->jenisKegiatan())->required()->live()
                ->afterStateUpdated(function (Set $set): void {
                    $set('kategori_lomba_id', null);
                    $set('capaian_id', null);
                }),
            Radio::make('bidang')->label('Bidang')->required()->inline()
                ->options(collect(BidangPrestasi::cases())->mapWithKeys(fn ($b) => [$b->value => $b->label()])),
            Select::make('kategori_lomba_id')->label('Kategori lomba')
                ->options(fn (Get $get) => app(OpsiMaster::class)->kategoriLomba($get('jenis_kegiatan_id') ?: null))
                ->placeholder('Pilih kategori'),
            TextInput::make('nama_kegiatan')->label('Nama kegiatan')->required()->maxLength(255),
            TextInput::make('judul_karya')->label('Judul karya (opsional)')->maxLength(255),
            TextInput::make('penyelenggara')->label('Penyelenggara')->required()->maxLength(255),
            TextInput::make('url_kegiatan')->label('Tautan kegiatan (opsional)')->url()->maxLength(500),
            DatePicker::make('tanggal_mulai')->label('Tanggal mulai')->required()->maxDate(now())->native(false)->live(),
            DatePicker::make('tanggal_selesai')->label('Tanggal selesai')->required()->native(false)
                ->minDate(fn (Get $get) => $get('tanggal_mulai'))->maxDate(now()),
            Radio::make('moda')->label('Moda')->required()->inline()->live()
                ->options(collect(ModaKegiatan::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()]))->default('luring'),
            TextInput::make('lokasi')->label('Lokasi (kota/tempat)')->maxLength(150)
                ->required(fn (Get $get) => in_array($get('moda'), ['luring', 'hibrida'], true)),
            TextInput::make('negara_penyelenggara')->label('Negara penyelenggara')->default('Indonesia')->maxLength(100),
            Textarea::make('deskripsi')->label('Deskripsi (teks polos)')->rows(3)->maxLength(2000)->columnSpanFull(),
        ]);
    }

    private static function langkahCapaian(): Step
    {
        return Step::make('Capaian & Tingkat')->columns(2)->schema([
            Select::make('tingkat_id')->label('Tingkat')
                ->options(fn () => app(OpsiMaster::class)->tingkat())->required()->live(),
            Select::make('capaian_id')->label('Capaian')
                ->options(fn (Get $get) => filled($get('jenis_kegiatan_id')) ? app(OpsiMaster::class)->capaian($get('jenis_kegiatan_id')) : [])
                ->required()->helperText('Pilih jenis kegiatan pada langkah 1 terlebih dahulu.'),
            TextInput::make('capaian_keterangan')->label('Keterangan capaian')->maxLength(150)->helperText('Mis. "Juara 1 Kategori Poster".'),
            Radio::make('bentuk')->label('Bentuk partisipasi')->required()->inline()->live()->default('individu')
                ->options(collect(BentukPartisipasi::cases())->mapWithKeys(fn ($b) => [$b->value => $b->label()])),
            TextInput::make('jumlah_peserta')->label('Jumlah peserta')->numeric()->integer()->minValue(1),
            TextInput::make('jumlah_pt_peserta')->label('Jumlah perguruan tinggi peserta')->numeric()->integer()->minValue(1)
                ->visible(fn (Get $get) => self::kodeTingkat($get) === 'nasional')
                ->required(fn (Get $get) => self::kodeTingkat($get) === 'nasional'),
            TextInput::make('jumlah_negara_peserta')->label('Jumlah negara peserta')->numeric()->integer()->minValue(1)
                ->visible(fn (Get $get) => self::kodeTingkat($get) === 'internasional')
                ->required(fn (Get $get) => self::kodeTingkat($get) === 'internasional')
                ->helperText(fn () => 'Ambang yang berlaku saat ini: '.Pengaturan::ambil('ambang_negara_internasional', 3).' negara (perlu verifikasi; hanya peringatan).'),
            Textarea::make('negara_peserta')->label('Daftar negara peserta (pisahkan dengan koma)')->rows(2)
                ->visible(fn (Get $get) => self::kodeTingkat($get) === 'internasional')
                ->required(fn (Get $get) => self::kodeTingkat($get) === 'internasional')->columnSpanFull(),
            TextInput::make('nomor_sertifikat')->label('Nomor sertifikat')->maxLength(100),
            Text::make(fn (Get $get): string => implode(' ', SimpanPrestasi::peringatan(
                ['tanggal_selesai' => $get('tanggal_selesai')],
                auth()->user()?->loadMissing('mahasiswa')->mahasiswa,
            )))->color('warning')->columnSpanFull()
                ->visible(fn (Get $get): bool => SimpanPrestasi::peringatan(
                    ['tanggal_selesai' => $get('tanggal_selesai')],
                    auth()->user()?->loadMissing('mahasiswa')->mahasiswa,
                ) !== []),
        ]);
    }

    private static function langkahTim(): Step
    {
        return Step::make('Tim & Pembimbing')->schema([
            Text::make('Anggota tim dan dosen pembimbing diisi pada langkah berikutnya.'),
        ]);
    }

    private static function langkahBukti(): Step
    {
        return Step::make('Tautan Bukti & Publikasi')->schema([
            Text::make('Tautan bukti diisi pada langkah berikutnya.'),
        ]);
    }

    private static function kodeTingkat(Get $get): ?string
    {
        return filled($get('tingkat_id')) ? app(OpsiMaster::class)->kodeTingkat((string) $get('tingkat_id')) : null;
    }
}

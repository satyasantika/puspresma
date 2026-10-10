<?php

namespace App\Filament\Admin\Resources;

use App\Actions\Katalog\ArsipkanKompetisi;
use App\Actions\Katalog\DuplikatKompetisi;
use App\Actions\Katalog\TerbitkanKompetisi;
use App\Enums\BidangPrestasi;
use App\Enums\ModaKegiatan;
use App\Enums\StatusKompetisi;
use App\Filament\Admin\Resources\KompetisiResource\Pages\CreateKompetisi;
use App\Filament\Admin\Resources\KompetisiResource\Pages\EditKompetisi;
use App\Filament\Admin\Resources\KompetisiResource\Pages\ListKompetisi;
use App\Models\Kompetisi;
use App\Models\Prodi;
use App\Rules\TautanBerkasValid;
use App\Services\OpsiMaster;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/** Pengelolaan katalog kompetisi; otorisasi lewat KompetisiPolicy (admin-prodi hanya sasaran prodinya sendiri). */
class KompetisiResource extends Resource
{
    protected static ?string $model = Kompetisi::class;

    protected static ?string $slug = 'kompetisi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $modelLabel = 'kompetisi';

    protected static ?string $pluralModelLabel = 'Kompetisi';

    protected static ?string $navigationLabel = 'Kompetisi';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $recordTitleAttribute = 'judul';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi')->columns(2)->schema([
                TextInput::make('judul')->label('Judul')->required()->maxLength(255)->columnSpanFull(),
                TextInput::make('penyelenggara')->label('Penyelenggara')->required()->maxLength(255),
                Select::make('jenis_kegiatan_id')->label('Jenis kegiatan')->options(fn () => app(OpsiMaster::class)->jenisKegiatan())->required(),
                Select::make('bidang')->label('Bidang')->options(collect(BidangPrestasi::cases())->mapWithKeys(fn ($b) => [$b->value => $b->label()])),
                Select::make('kategori_lomba_id')->label('Kategori')->options(fn () => app(OpsiMaster::class)->kategoriLomba()),
                Select::make('tingkat_id')->label('Tingkat')->options(fn () => app(OpsiMaster::class)->tingkat()),
                Select::make('moda')->label('Moda')->options(collect(ModaKegiatan::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()])),
                RichEditor::make('deskripsi')->label('Deskripsi')->columnSpanFull()
                    ->toolbarButtons([['bold', 'italic', 'h3', 'h4', 'blockquote', 'bulletList', 'orderedList', 'link', 'undo', 'redo']])
                    ->helperText('HTML disanitasi otomatis; tanpa skrip atau gambar.'),
            ]),
            Section::make('Jadwal & pendaftaran')->columns(2)->schema([
                DatePicker::make('tanggal_buka')->label('Pendaftaran dibuka')->native(false),
                DateTimePicker::make('tenggat_pendaftaran')->label('Tenggat pendaftaran')->required()->native(false),
                DatePicker::make('tanggal_pelaksanaan_mulai')->label('Pelaksanaan mulai')->native(false),
                DatePicker::make('tanggal_pelaksanaan_selesai')->label('Pelaksanaan selesai')->native(false)->afterOrEqual('tanggal_pelaksanaan_mulai'),
                TextInput::make('lokasi')->label('Lokasi')->maxLength(150),
                TextInput::make('biaya_pendaftaran')->label('Biaya pendaftaran')->numeric()->prefix('Rp')->minValue(0)->step(0.01),
                TextInput::make('url_pendaftaran')->label('Tautan pendaftaran')->url()->maxLength(500),
                TextInput::make('url_panduan')->label('Tautan panduan')->url()->maxLength(500),
                TextInput::make('kontak_narahubung')->label('Narahubung')->maxLength(150),
            ]),
            Section::make('Poster & sasaran')->columns(2)->schema([
                TextInput::make('url_poster')->label('Tautan poster (Google Drive)')->maxLength(2048)->rule(new TautanBerkasValid)
                    ->helperText('Tempel tautan gambar poster; bagikan "Siapa saja yang memiliki link". Tidak ada unggahan berkas.'),
                Select::make('prodi_sasaran')->label('Sasaran prodi')->multiple()->searchable()
                    ->options(fn () => Prodi::query()->orderBy('nama')->pluck('nama', 'id')->all())
                    ->visible(fn (): bool => (bool) auth()->user()?->can('kompetisi.kelola'))
                    ->helperText('Kosong = semua prodi.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('tingkat'))
            ->defaultSort('tenggat_pendaftaran', 'desc')
            ->columns([
                TextColumn::make('judul')->label('Judul')->searchable()->limit(50)->wrap(),
                TextColumn::make('penyelenggara')->label('Penyelenggara')->limit(30),
                TextColumn::make('tingkat.nama')->label('Tingkat')->placeholder('—'),
                TextColumn::make('tenggat_pendaftaran')->label('Tenggat')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (?StatusKompetisi $state) => $state?->label())->color(fn (?StatusKompetisi $state) => $state?->warna()),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(collect(StatusKompetisi::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                EditAction::make(),
                self::aksi('terbitkan', 'Terbitkan', 'heroicon-o-megaphone', fn (Kompetisi $k) => app(TerbitkanKompetisi::class)->jalankan($k, auth()->user()), 'Kompetisi diterbitkan',
                    fn (Kompetisi $k): bool => $k->status === StatusKompetisi::Draf),
                self::aksi('arsipkan', 'Arsipkan', 'heroicon-o-archive-box', fn (Kompetisi $k) => app(ArsipkanKompetisi::class)->jalankan($k, auth()->user()), 'Kompetisi diarsipkan',
                    fn (Kompetisi $k): bool => $k->status === StatusKompetisi::Terbit),
                self::aksi('duplikat', 'Duplikat sebagai draf', 'heroicon-o-document-duplicate', fn (Kompetisi $k) => app(DuplikatKompetisi::class)->jalankan($k, auth()->user()), 'Draf baru dibuat dari salinan',
                    fn (): bool => true),
                DeleteAction::make(),
            ]);
    }

    /** Aksi baris yang tampil sesuai Policy dan mengubah galat domain menjadi notifikasi. */
    private static function aksi(string $nama, string $label, string $ikon, \Closure $kerja, string $sukses, \Closure $tampilBila): Action
    {
        return Action::make($nama)->label($label)->icon($ikon)
            ->visible(fn (Kompetisi $record): bool => $tampilBila($record) && (bool) auth()->user()?->can('update', $record))
            ->requiresConfirmation()
            ->action(function (Kompetisi $record) use ($kerja, $sukses): void {
                try {
                    $kerja($record);
                } catch (ValidationException|AuthorizationException $e) {
                    Notification::make()->title('Tidak dapat diproses')->body(collect($e instanceof ValidationException ? $e->errors() : [$e->getMessage()])->flatten()->implode("\n"))->danger()->send();

                    return;
                }
                Notification::make()->title($sukses)->success()->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKompetisi::route('/'),
            'create' => CreateKompetisi::route('/create'),
            'edit' => EditKompetisi::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources;

use App\Actions\Surat\CabutSuratKeteranganPrestasi;
use App\Enums\StatusSurat;
use App\Filament\Admin\Resources\SuratKeteranganPrestasiResource\Pages\CreateSuratKeteranganPrestasi;
use App\Filament\Admin\Resources\SuratKeteranganPrestasiResource\Pages\ListSuratKeteranganPrestasi;
use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\SuratKeteranganPrestasi;
use App\Services\CetakSuratKeteranganPrestasi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/** Surat Keterangan Prestasi ber-QR; PDF di-stream saat dicetak dan tidak disimpan. */
class SuratKeteranganPrestasiResource extends Resource
{
    protected static ?string $model = SuratKeteranganPrestasi::class;

    protected static ?string $slug = 'surat-keterangan-prestasi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?string $modelLabel = 'surat keterangan prestasi';

    protected static ?string $pluralModelLabel = 'Surat Keterangan Prestasi';

    protected static ?string $navigationLabel = 'Surat Keterangan';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->canAny(['surat.terbitkan', 'prestasi.lihat-semua', 'prestasi.lihat-prodi']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('mahasiswa_id')->label('Mahasiswa')->searchable()->required()->live()
                ->getSearchResultsUsing(fn (string $search): array => mb_strlen(trim($search)) < 2 ? [] : Mahasiswa::query()->where(fn ($q) => $q->where('npm', 'like', "{$search}%")->orWhere('nama', 'like', "%{$search}%"))->limit(20)->get()->mapWithKeys(fn ($m) => [$m->id => "{$m->npm} — {$m->nama}"])->all())
                ->getOptionLabelUsing(fn ($value): ?string => ($m = Mahasiswa::find($value)) === null ? null : "{$m->npm} — {$m->nama}"),
            CheckboxList::make('prestasi_ids')->label('Prestasi terverifikasi')->required()
                ->options(fn (Get $get): array => filled($get('mahasiswa_id'))
                    ? Prestasi::query()->terverifikasi()->milikMahasiswa($get('mahasiswa_id'))->orderByDesc('tanggal_selesai')->pluck('nama_kegiatan', 'id')->all() : [])
                ->helperText('Pilih mahasiswa terlebih dahulu; hanya prestasi terverifikasi miliknya yang tampil.'),
            TextInput::make('keperluan')->label('Keperluan')->required()->maxLength(100)->helperText('Mis. Beasiswa, SKPI, Pilmapres.'),
            DatePicker::make('periode_mulai')->label('Periode mulai')->native(false),
            DatePicker::make('periode_selesai')->label('Periode selesai')->native(false)->afterOrEqual('periode_mulai'),
            TextInput::make('penandatangan_nama')->label('Nama penandatangan')->required()->maxLength(150),
            TextInput::make('penandatangan_jabatan')->label('Jabatan penandatangan')->required()->maxLength(150),
            TextInput::make('penandatangan_nip')->label('NIP penandatangan')->maxLength(30),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                /** @var Builder<SuratKeteranganPrestasi> $query */
                $pengguna = auth()->user();
                if ($pengguna !== null && ! $pengguna->canAny(['surat.terbitkan', 'prestasi.lihat-semua'])) {
                    $query->whereHas('mahasiswa', fn ($q) => $q->where('prodi_id', $pengguna->prodi_id));
                }

                return $query->with('mahasiswa');
            })
            ->defaultSort('tanggal_terbit', 'desc')
            ->columns([
                TextColumn::make('nomor')->label('Nomor')->searchable(),
                TextColumn::make('mahasiswa.nama')->label('Mahasiswa')->searchable(),
                TextColumn::make('keperluan')->label('Keperluan'),
                TextColumn::make('tanggal_terbit')->label('Terbit')->date('d M Y')->sortable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (?StatusSurat $state) => $state?->label())->color(fn (?StatusSurat $state) => $state?->warna()),
            ])
            ->recordActions([
                Action::make('cetak')->label('Cetak PDF')->icon('heroicon-o-printer')
                    ->visible(fn (SuratKeteranganPrestasi $record): bool => (bool) auth()->user()?->can('cetak', $record))
                    ->action(fn (SuratKeteranganPrestasi $record) => app(CetakSuratKeteranganPrestasi::class)->stream($record)),
                Action::make('cabut')->label('Cabut')->icon('heroicon-o-no-symbol')->color('danger')
                    ->visible(fn (SuratKeteranganPrestasi $record): bool => $record->status === StatusSurat::Aktif && (bool) auth()->user()?->can('cabut', $record))
                    ->schema([Textarea::make('alasan')->label('Alasan pencabutan')->required()->maxLength(255)])
                    ->action(function (SuratKeteranganPrestasi $record, array $data): void {
                        try {
                            app(CabutSuratKeteranganPrestasi::class)->jalankan($record, auth()->user(), (string) $data['alasan']);
                        } catch (ValidationException $e) {
                            Notification::make()->title('Tidak dapat dicabut')->body(collect($e->errors())->flatten()->implode("\n"))->danger()->send();

                            return;
                        }
                        Notification::make()->title('Surat dicabut')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuratKeteranganPrestasi::route('/'),
            'create' => CreateSuratKeteranganPrestasi::route('/create'),
        ];
    }
}

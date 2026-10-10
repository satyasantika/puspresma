<?php

namespace App\Filament\Portal\Resources;

use App\Actions\Pembimbing\KonfirmasiPembimbingan;
use App\Actions\Pembimbing\SangkalPembimbingan;
use App\Enums\JenisTautan;
use App\Enums\StatusKonfirmasiPembimbing;
use App\Filament\Portal\Resources\KonfirmasiPembimbingResource\Pages\ListKonfirmasiPembimbing;
use App\Models\Prestasi;
use App\Models\TautanBerkas;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/** Permintaan konfirmasi pembimbingan untuk dosen login (tanpa kontak mahasiswa, BR-26). */
class KonfirmasiPembimbingResource extends Resource
{
    protected static ?string $model = Prestasi::class;

    protected static ?string $slug = 'konfirmasi-pembimbing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $modelLabel = 'permintaan konfirmasi';

    protected static ?string $pluralModelLabel = 'Konfirmasi Pembimbing';

    protected static ?string $navigationLabel = 'Konfirmasi Pembimbing';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('pembimbing.konfirmasi') && parent::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Dosen yang ditunjuk; status prestasi sudah lewat draf.
     *
     * @return Builder<Prestasi>
     */
    public static function queryDosen(string $statusKonfirmasi): Builder
    {
        $dosenId = auth()->user()?->loadMissing('dosen')->dosen?->id;

        return Prestasi::query()
            ->when($dosenId === null, fn ($q) => $q->whereRaw('1 = 0'), fn ($q) => $q->where('dosen_pembimbing_id', $dosenId))
            ->where('status_konfirmasi_pembimbing', $statusKonfirmasi)
            ->where('status', '!=', 'draf');
    }

    public static function getNavigationBadge(): ?string
    {
        $jumlah = static::canViewAny() ? static::queryDosen(StatusKonfirmasiPembimbing::Menunggu->value)->count() : 0;

        return $jumlah > 0 ? (string) $jumlah : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => static::queryDosen(StatusKonfirmasiPembimbing::Menunggu->value)->with(['pengaju', 'tingkat', 'capaian']))
            ->defaultSort('diajukan_at')
            ->columns([
                TextColumn::make('nama_kegiatan')->label('Kegiatan')->wrap()->limit(60),
                TextColumn::make('pengaju.nama')->label('Mahasiswa')
                    ->formatStateUsing(fn (?string $state, Prestasi $record): string => "{$record->pengaju->npm} — {$state}"),
                TextColumn::make('tingkat.nama')->label('Tingkat'),
                TextColumn::make('capaian.nama')->label('Capaian'),
                TextColumn::make('tanggal_selesai')->label('Selesai')->date('d M Y'),
            ])
            ->recordActions([
                Action::make('bukaSertifikat')->label('Buka berkas')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                    ->visible(fn (Prestasi $record): bool => self::sertifikat($record) !== null)
                    ->url(fn (Prestasi $record): string => route('tautan.buka', self::sertifikat($record)), shouldOpenInNewTab: true),
                Action::make('konfirmasi')->label('Konfirmasi')->icon('heroicon-o-check')->color('success')
                    ->visible(fn (Prestasi $record): bool => (bool) auth()->user()?->can('jawabPembimbing', $record))
                    ->requiresConfirmation()->modalDescription('Saya membenarkan telah membimbing mahasiswa pada kegiatan ini.')
                    ->action(function (Prestasi $record): void {
                        app(KonfirmasiPembimbingan::class)->jalankan($record, auth()->user());
                        Notification::make()->title('Pembimbingan dikonfirmasi')->success()->send();
                    }),
                Action::make('sangkal')->label('Sangkal')->icon('heroicon-o-x-mark')->color('danger')
                    ->visible(fn (Prestasi $record): bool => (bool) auth()->user()?->can('jawabPembimbing', $record))
                    ->schema([Textarea::make('alasan')->label('Alasan (min. 10 karakter)')->required()->minLength(10)->maxLength(2000)])
                    ->action(function (Prestasi $record, array $data): void {
                        try {
                            app(SangkalPembimbingan::class)->jalankan($record, auth()->user(), (string) $data['alasan']);
                        } catch (ValidationException|AuthorizationException $e) {
                            Notification::make()->title('Tidak dapat diproses')->body($e->getMessage())->danger()->send();

                            return;
                        }
                        Notification::make()->title('Pembimbingan disangkal')->success()->send();
                    }),
            ]);
    }

    private static function sertifikat(Prestasi $prestasi): ?TautanBerkas
    {
        return $prestasi->tautanBukti()->where('jenis', JenisTautan::SertifikatPiagam->value)->first();
    }

    public static function getPages(): array
    {
        return ['index' => ListKonfirmasiPembimbing::route('/')];
    }
}

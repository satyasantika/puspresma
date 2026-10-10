<?php

namespace App\Filament\Admin\Pages;

use App\Actions\Galeri\SembunyikanDariGaleri;
use App\Actions\Galeri\TampilkanKembaliDiGaleri;
use App\Models\Prestasi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/** Moderasi galeri publik: sembunyikan/tampilkan kembali prestasi yang disetujui pengaju (galeri.moderasi). */
class ModerasiGaleri extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'moderasi-galeri';

    protected static ?string $title = 'Moderasi Galeri';

    protected static ?string $navigationLabel = 'Moderasi Galeri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEyeSlash;

    protected static string|UnitEnum|null $navigationGroup = 'Prestasi';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('galeri.moderasi');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Prestasi::query()->terverifikasi()
                ->whereHas('anggota', fn (Builder $q) => $q->where('is_pengaju', true)->where('setuju_publikasi', true))
                ->with(['tingkat', 'capaian']))
            ->defaultSort('tanggal_selesai', 'desc')
            ->columns([
                TextColumn::make('nama_kegiatan')->label('Prestasi')->wrap()->limit(60)->searchable(),
                TextColumn::make('tingkat.nama')->label('Tingkat'),
                TextColumn::make('capaian.nama')->label('Capaian'),
                IconColumn::make('disembunyikan_dari_galeri')->label('Disembunyikan')->boolean(),
                TextColumn::make('alasan_disembunyikan')->label('Alasan')->placeholder('—')->wrap()->limit(60),
            ])
            ->recordActions([
                Action::make('sembunyikan')->label('Sembunyikan')->icon('heroicon-o-eye-slash')->color('danger')
                    ->visible(fn (Prestasi $record): bool => ! $record->disembunyikan_dari_galeri)
                    ->schema([Textarea::make('alasan')->label('Alasan (min. 10 karakter)')->required()->minLength(10)->maxLength(255)])
                    ->action(function (Prestasi $record, array $data): void {
                        try {
                            app(SembunyikanDariGaleri::class)->jalankan($record, auth()->user(), (string) $data['alasan']);
                        } catch (ValidationException $e) {
                            Notification::make()->title('Tidak dapat diproses')->body(collect($e->errors())->flatten()->implode("\n"))->danger()->send();

                            return;
                        }
                        Notification::make()->title('Prestasi disembunyikan dari galeri')->success()->send();
                    }),
                Action::make('tampilkan')->label('Tampilkan kembali')->icon('heroicon-o-eye')
                    ->visible(fn (Prestasi $record): bool => $record->disembunyikan_dari_galeri)
                    ->requiresConfirmation()
                    ->action(function (Prestasi $record): void {
                        app(TampilkanKembaliDiGaleri::class)->jalankan($record, auth()->user());
                        Notification::make()->title('Prestasi ditampilkan kembali')->success()->send();
                    }),
            ]);
    }
}

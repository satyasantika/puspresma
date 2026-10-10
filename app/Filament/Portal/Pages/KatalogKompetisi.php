<?php

namespace App\Filament\Portal\Pages;

use App\Actions\Katalog\UbahMinatKompetisi;
use App\Models\Kompetisi;
use App\Models\KompetisiMinat;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/** Katalog kompetisi di portal (mahasiswa & dosen) dengan penanda minat dan sasaran prodi. */
class KatalogKompetisi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'katalog-kompetisi';

    protected static ?string $title = 'Katalog Kompetisi';

    protected static ?string $navigationLabel = 'Katalog Kompetisi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['mahasiswa', 'dosen']);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        $pengguna = auth()->user()?->loadMissing('mahasiswa');
        $mahasiswaId = $pengguna?->mahasiswa?->id;
        $prodiId = $pengguna?->prodi_id;

        return $table
            ->query(fn (): Builder => Kompetisi::query()->masihDibuka()->with(['tingkat', 'prodiSasaran']))
            ->defaultSort('tenggat_pendaftaran')
            ->columns([
                TextColumn::make('judul')->label('Kompetisi')->searchable()->wrap()->limit(60),
                TextColumn::make('penyelenggara')->label('Penyelenggara')->limit(30),
                TextColumn::make('tingkat.nama')->label('Tingkat')->placeholder('—'),
                TextColumn::make('tenggat_pendaftaran')->label('Tenggat')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('untuk_prodi')->label('')->badge()->color('success')
                    ->state(fn (Kompetisi $record): ?string => $prodiId !== null && $record->prodiSasaran->contains('id', $prodiId) ? 'Untuk prodi Anda' : null),
                TextColumn::make('minat_saya')->label('Minat')->badge()
                    ->state(fn (Kompetisi $record): ?string => $mahasiswaId !== null && KompetisiMinat::where('kompetisi_id', $record->id)->where('mahasiswa_id', $mahasiswaId)->exists() ? 'Berminat' : null),
            ])
            ->filters([SelectFilter::make('tingkat_id')->label('Tingkat')->relationship('tingkat', 'nama')])
            ->recordActions([
                Action::make('lihat')->label('Detail')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                    ->url(fn (Kompetisi $record): string => route('kompetisi.show', $record->slug), shouldOpenInNewTab: true),
                Action::make('berminat')->label('Berminat')->icon('heroicon-o-heart')
                    ->visible(fn (Kompetisi $record): bool => $mahasiswaId !== null && ! KompetisiMinat::where('kompetisi_id', $record->id)->where('mahasiswa_id', $mahasiswaId)->exists())
                    ->action(fn (Kompetisi $record) => $this->ubahMinat($record, true)),
                Action::make('batalMinat')->label('Batal minat')->icon('heroicon-o-x-mark')->color('gray')
                    ->visible(fn (Kompetisi $record): bool => $mahasiswaId !== null && KompetisiMinat::where('kompetisi_id', $record->id)->where('mahasiswa_id', $mahasiswaId)->exists())
                    ->action(fn (Kompetisi $record) => $this->ubahMinat($record, false)),
            ]);
    }

    private function ubahMinat(Kompetisi $kompetisi, bool $berminat): void
    {
        try {
            app(UbahMinatKompetisi::class)->jalankan($kompetisi, auth()->user(), $berminat);
        } catch (ValidationException $e) {
            Notification::make()->title('Tidak dapat diproses')->body(collect($e->errors())->flatten()->implode("\n"))->danger()->send();

            return;
        }

        Notification::make()->title($berminat ? 'Ditandai berminat' : 'Minat dibatalkan')->success()->send();
    }
}

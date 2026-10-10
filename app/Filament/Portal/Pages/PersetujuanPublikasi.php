<?php

namespace App\Filament\Portal\Pages;

use App\Actions\Galeri\UbahPersetujuanPublikasi;
use App\Enums\StatusPrestasi;
use App\Models\Mahasiswa;
use App\Models\PrestasiAnggota;
use App\Services\Pengaturan;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Persetujuan publikasi per prestasi (opt-in, dapat ditarik) beserta alasan prestasi/foto belum tampil. */
class PersetujuanPublikasi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'persetujuan-publikasi';

    protected static ?string $title = 'Persetujuan Publikasi';

    protected static ?string $navigationLabel = 'Persetujuan Publikasi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('prestasi.buat-milik');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Text::make(fn (): string => (string) Pengaturan::ambil('teks_persetujuan_publikasi', ''))->columnSpanFull(),
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        $mahasiswaId = Mahasiswa::firstWhere('user_id', auth()->id())?->id;

        return $table
            ->query(fn (): Builder => PrestasiAnggota::query()
                ->when($mahasiswaId === null, fn ($q) => $q->whereRaw('1 = 0'), fn ($q) => $q->where('mahasiswa_id', $mahasiswaId))
                ->with('prestasi'))
            ->columns([
                TextColumn::make('prestasi.nama_kegiatan')->label('Prestasi')->wrap()->limit(60),
                TextColumn::make('prestasi.status')->label('Status')->badge()
                    ->formatStateUsing(fn (?StatusPrestasi $state) => $state?->label())->color(fn (?StatusPrestasi $state) => $state?->warna()),
                ToggleColumn::make('setuju_publikasi')->label('Saya setuju')
                    ->updateStateUsing(function (PrestasiAnggota $record, bool $state): bool {
                        app(UbahPersetujuanPublikasi::class)->jalankan($record, auth()->user(), $state);

                        return $state;
                    }),
                TextColumn::make('status_galeri')->label('Tampil di galeri')->wrap()
                    ->state(function (PrestasiAnggota $record): string {
                        $alasan = $record->prestasi->alasanTidakTampilDiGaleri();
                        if ($alasan === []) {
                            $foto = $record->prestasi->alasanFotoTidakTampil();

                            return $foto === [] ? 'Ya' : 'Ya, tanpa foto: '.implode(' ', $foto);
                        }

                        return 'Tidak — '.implode(' ', $alasan);
                    }),
            ]);
    }
}

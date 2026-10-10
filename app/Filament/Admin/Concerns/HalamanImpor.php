<?php

namespace App\Filament\Admin\Concerns;

use App\Enums\StatusImpor;
use App\Models\RiwayatImpor;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Halaman impor master dari Excel/CSV: unduh templat (stream), unggah berkas ke disk 'tmp' lalu antre,
 * dan riwayat impor. Satu-satunya tempat input berkas yang diizinkan (STANDAR-TEKNIS §1a).
 */
trait HalamanImpor
{
    use InteractsWithTable;

    /** @return 'mahasiswa'|'dosen' */
    abstract protected static function jenisImpor(): string;

    /** @return list<string> */
    abstract protected static function kolomTemplat(): array;

    /** @return list<array<string, string>> */
    abstract protected static function contohTemplat(): array;

    /** @param  class-string<ShouldQueue>  $job */
    abstract protected static function kelasJob(): string;

    abstract protected static function izin(): string;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(static::izin());
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('unduhTemplat')->label('Unduh templat')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(fn (): StreamedResponse => $this->unduhTemplat()),
            Action::make('proses')->label('Impor berkas')->icon('heroicon-o-arrow-up-tray')
                ->modalSubmitActionLabel('Proses')
                ->schema([
                    FileUpload::make('berkas')->label('Berkas XLSX/CSV')
                        ->disk('tmp')->directory('impor')->visibility('private')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel',
                        ])
                        ->rule('extensions:'.implode(',', config('puspresma.impor.mimes')))
                        ->maxSize(config('puspresma.impor.maks_kb'))
                        ->storeFileNamesIn('nama_asli')
                        ->required(),
                ])
                ->action(fn (array $data) => $this->antre($data)),
        ];
    }

    /** @param  array<string, mixed>  $data */
    protected function antre(array $data): void
    {
        $riwayat = RiwayatImpor::create([
            'jenis' => static::jenisImpor(),
            'nama_berkas' => $data['nama_asli'] ?? basename((string) $data['berkas']),
            'path_tmp' => $data['berkas'],
            'status' => StatusImpor::Antre,
            'user_id' => auth()->id(),
        ]);

        (static::kelasJob())::dispatch($riwayat);

        Notification::make()->title('Impor dimasukkan ke antrean')->success()->send();
    }

    protected function unduhTemplat(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $penulis = SimpleExcelWriter::create('php://output', 'xlsx');
            foreach (static::contohTemplat() as $baris) {
                $penulis->addRow($baris);
            }
            $penulis->close();
        }, 'templat-impor-'.static::jenisImpor().'.xlsx');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => RiwayatImpor::query()->with('user')->where('jenis', static::jenisImpor()))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('nama_berkas')->label('Berkas')->limit(40),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (?StatusImpor $state) => $state?->label())
                    ->color(fn (?StatusImpor $state) => $state?->warna()),
                TextColumn::make('total_baris')->label('Total'),
                TextColumn::make('berhasil')->label('Berhasil'),
                TextColumn::make('gagal')->label('Gagal'),
                TextColumn::make('user.name')->label('Oleh'),
                TextColumn::make('pesan')->label('Catatan')->limit(40)->placeholder('—'),
            ])
            ->recordActions([
                Action::make('unduhGalat')->label('Unduh galat')->icon('heroicon-o-exclamation-triangle')->color('warning')
                    ->visible(fn (RiwayatImpor $record): bool => $record->galatTersedia())
                    ->action(fn (RiwayatImpor $record) => Storage::disk('tmp')->download($record->path_galat, 'galat-impor-'.static::jenisImpor().'.xlsx')),
            ]);
    }
}

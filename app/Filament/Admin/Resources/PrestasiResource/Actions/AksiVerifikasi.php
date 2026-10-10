<?php

namespace App\Filament\Admin\Resources\PrestasiResource\Actions;

use App\Actions\Bukti\GantiTautanTerverifikasi;
use App\Actions\Prestasi\BukaVerifikasiPrestasi;
use App\Actions\Prestasi\KembalikanUntukPerbaikan;
use App\Actions\Prestasi\LoloskanTahapProdi;
use App\Actions\Prestasi\TolakPrestasi;
use App\Actions\Prestasi\VerifikasiPrestasi;
use App\Enums\JenisTautan;
use App\Enums\StatusKonfirmasiPembimbing;
use App\Enums\StatusPrestasi;
use App\Exceptions\TransisiTidakSah;
use App\Models\Aktivitas;
use App\Models\Prestasi;
use App\Rules\TautanBerkasValid;
use App\Services\Pengaturan;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/** Aksi verifikator di panel admin; semua logika di Action bisnis F5.1, galat dijadikan notifikasi Filament. */
class AksiVerifikasi
{
    /** @return list<Action> */
    public static function semua(): array
    {
        return [self::loloskanProdi(), self::kembalikan(), self::tolak(), self::verifikasi(), self::bukaVerifikasi(), self::gantiTautan()];
    }

    public static function loloskanProdi(): Action
    {
        return Action::make('loloskanProdi')->label('Lolos Tahap Prodi')->icon('heroicon-o-check')->color('info')
            ->visible(fn (Prestasi $record): bool => (bool) auth()->user()?->can('loloskanProdi', $record))
            ->schema([Textarea::make('catatan')->label('Catatan (opsional)')->maxLength(1000)])
            ->action(fn (Prestasi $record, array $data) => self::jalankan(
                fn () => app(LoloskanTahapProdi::class)->jalankan($record, auth()->user(), $data['catatan'] ?? null),
                'Prestasi diloloskan ke tahap fakultas',
            ));
    }

    public static function kembalikan(): Action
    {
        return Action::make('kembalikan')->label('Kembalikan untuk Perbaikan')->icon('heroicon-o-arrow-uturn-left')->color('warning')
            ->visible(fn (Prestasi $record): bool => (bool) auth()->user()?->can('kembalikan', $record))
            ->schema([self::catatan('Catatan perbaikan untuk mahasiswa (min. 10 karakter)')])
            ->action(fn (Prestasi $record, array $data) => self::jalankan(
                fn () => app(KembalikanUntukPerbaikan::class)->jalankan($record, auth()->user(), (string) $data['catatan']),
                'Prestasi dikembalikan untuk perbaikan',
            ));
    }

    public static function tolak(): Action
    {
        return Action::make('tolak')->label('Tolak')->icon('heroicon-o-x-circle')->color('danger')
            ->visible(fn (Prestasi $record): bool => (bool) auth()->user()?->can('tolak', $record))
            ->modalDescription('Penolakan bersifat final. Mahasiswa hanya dapat membuat pengajuan baru.')
            ->schema([
                self::catatan('Alasan penolakan (min. 10 karakter)'),
                Checkbox::make('yakin')->label('Saya yakin menolak prestasi ini.')->accepted(),
            ])
            ->action(fn (Prestasi $record, array $data) => self::jalankan(
                fn () => app(TolakPrestasi::class)->jalankan($record, auth()->user(), (string) $data['catatan']),
                'Prestasi ditolak',
            ));
    }

    public static function verifikasi(): Action
    {
        return Action::make('verifikasi')->label('Verifikasi')->icon('heroicon-o-check-badge')->color('success')
            ->visible(fn (Prestasi $record): bool => (bool) auth()->user()?->can('verifikasi', $record))
            ->modalHeading('Verifikasi prestasi')
            ->schema(fn (Prestasi $record): array => [
                Textarea::make('peringatan_tautan')->label('Perhatian')->disabled()->dehydrated(false)->rows(2)
                    ->default(fn (): string => self::peringatanTautan($record))
                    ->visible(fn (): bool => self::peringatanTautan($record) !== ''),
                Checkbox::make('sudah_membuka_tautan')->label('Saya telah membuka dan memeriksa semua tautan bukti')->accepted(),
                Toggle::make('ambil_alih')->label('Ambil alih (verifikasi langsung tanpa tahap prodi)')
                    ->visible(fn (): bool => $record->status === StatusPrestasi::Diajukan && (bool) Pengaturan::ambil('verifikasi_prodi_aktif', false)),
                Toggle::make('tanpa_konfirmasi')->label('Verifikasi tanpa konfirmasi dosen pembimbing')
                    ->visible(fn (): bool => $record->status_konfirmasi_pembimbing === StatusKonfirmasiPembimbing::Menunggu),
                Textarea::make('catatan')->label('Catatan (wajib bila mengambil alih / tanpa konfirmasi pembimbing)')->maxLength(2000),
            ])
            ->action(fn (Prestasi $record, array $data) => self::jalankan(
                fn () => app(VerifikasiPrestasi::class)->jalankan(
                    $record, auth()->user(), $data['catatan'] ?? null, (bool) ($data['ambil_alih'] ?? false), (bool) ($data['tanpa_konfirmasi'] ?? false), (bool) $data['sudah_membuka_tautan'],
                ),
                'Prestasi diverifikasi',
            ));
    }

    public static function bukaVerifikasi(): Action
    {
        return Action::make('bukaVerifikasi')->label('Buka Verifikasi')->icon('heroicon-o-lock-open')->color('warning')
            ->visible(fn (Prestasi $record): bool => (bool) auth()->user()?->can('bukaVerifikasi', $record))
            ->modalDescription('Prestasi kembali ke "perlu perbaikan" dan poin anggota dikosongkan. Snapshot tautan lama tetap tersimpan.')
            ->schema([self::catatan('Alasan membuka verifikasi (min. 10 karakter)')])
            ->action(fn (Prestasi $record, array $data) => self::jalankan(
                fn () => app(BukaVerifikasiPrestasi::class)->jalankan($record, auth()->user(), (string) $data['catatan']),
                'Verifikasi dibuka kembali',
            ));
    }

    public static function gantiTautan(): Action
    {
        return Action::make('gantiTautan')->label('Ganti Tautan')->icon('heroicon-o-link')->color('gray')
            ->visible(fn (Prestasi $record): bool => (bool) auth()->user()?->can('gantiTautan', $record))
            ->schema(fn (Prestasi $record): array => [
                Select::make('tautan_id')->label('Tautan yang diganti')->required()
                    ->options(fn () => $record->tautanBukti()->get()->mapWithKeys(fn ($t) => [$t->id => $t->jenis->label().' — '.$t->url])->all()),
                TextInput::make('url_baru')->label('Tautan baru (https)')->required()->maxLength(2048)->rule(new TautanBerkasValid),
                self::catatan('Alasan penggantian (min. 10 karakter)'),
            ])
            ->action(fn (Prestasi $record, array $data) => self::jalankan(
                fn () => app(GantiTautanTerverifikasi::class)->jalankan($record, $record->tautanBukti()->findOrFail($data['tautan_id']), (string) $data['url_baru'], (string) $data['catatan'], auth()->user()),
                'Tautan diganti dan snapshot baru disimpan',
            ));
    }

    private static function catatan(string $label): Textarea
    {
        return Textarea::make('catatan')->label($label)->required()->minLength(10)->maxLength(2000);
    }

    /** Peringatan bila ada tautan sertifikat/piagam yang belum pernah dibuka verifikator ini (BR-29). */
    private static function peringatanTautan(Prestasi $record): string
    {
        $sertifikat = $record->tautanBukti()->where('jenis', JenisTautan::SertifikatPiagam->value)->pluck('id');
        $dibuka = Aktivitas::where('log_name', 'tautan')->where('event', 'dibuka')->where('causer_id', auth()->id())
            ->whereIn('subject_id', $sertifikat)->pluck('subject_id')->unique();
        $belum = $sertifikat->diff($dibuka)->count();

        return $belum > 0 ? "{$belum} tautan sertifikat/piagam belum Anda buka lewat tombol \"Buka berkas\"." : '';
    }

    /** Menjalankan Action bisnis dan mengubah galat domain menjadi notifikasi. */
    private static function jalankan(Closure $kerja, string $sukses): void
    {
        try {
            $kerja();
        } catch (ValidationException $e) {
            Notification::make()->title('Tidak dapat diproses')->body(collect($e->errors())->flatten()->implode("\n"))->danger()->send();

            return;
        } catch (TransisiTidakSah|AuthorizationException $e) {
            Notification::make()->title('Tidak dapat diproses')->body($e->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title($sukses)->success()->send();
    }
}

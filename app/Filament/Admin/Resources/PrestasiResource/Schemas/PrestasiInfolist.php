<?php

namespace App\Filament\Admin\Resources\PrestasiResource\Schemas;

use App\Enums\StatusKonfirmasiPembimbing;
use App\Enums\StatusPrestasi;
use App\Models\Aktivitas;
use App\Models\Prestasi;
use App\Models\TautanBerkas;
use App\Services\PeringatanPrestasi;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class PrestasiInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])->columnSpanFull()->schema([
                Group::make([
                    Section::make('Peringatan')->visible(fn (Prestasi $record): bool => PeringatanPrestasi::untuk($record) !== [])->schema([
                        TextEntry::make('peringatan')->hiddenLabel()->color('warning')->listWithLineBreaks()->bulleted()
                            ->state(fn (Prestasi $record): array => PeringatanPrestasi::untuk($record)),
                    ]),
                    Section::make('Data prestasi')->columns(2)->schema([
                        TextEntry::make('nomor_registrasi')->label('No. registrasi')->placeholder('—'),
                        TextEntry::make('status')->label('Status')->badge()
                            ->formatStateUsing(fn (?StatusPrestasi $state) => $state?->label())->color(fn (?StatusPrestasi $state) => $state?->warna()),
                        TextEntry::make('nama_kegiatan')->label('Kegiatan')->columnSpanFull(),
                        TextEntry::make('judul_karya')->label('Judul karya')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('penyelenggara')->label('Penyelenggara'),
                        TextEntry::make('jenisKegiatan.nama')->label('Jenis'),
                        TextEntry::make('bidang')->label('Bidang')->formatStateUsing(fn ($state) => $state?->label()),
                        TextEntry::make('kategoriLomba.nama')->label('Kategori')->placeholder('—'),
                        TextEntry::make('tingkat.nama')->label('Tingkat'),
                        TextEntry::make('capaian.nama')->label('Capaian'),
                        TextEntry::make('capaian_keterangan')->label('Keterangan capaian')->placeholder('—'),
                        TextEntry::make('bentuk')->label('Bentuk')->formatStateUsing(fn ($state) => $state?->label()),
                        TextEntry::make('tanggal_mulai')->label('Mulai')->date('d F Y'),
                        TextEntry::make('tanggal_selesai')->label('Selesai')->date('d F Y'),
                        TextEntry::make('moda')->label('Moda')->formatStateUsing(fn ($state) => $state?->label()),
                        TextEntry::make('lokasi')->label('Lokasi')->placeholder('—'),
                        TextEntry::make('jumlah_peserta')->label('Jumlah peserta')->placeholder('—'),
                        TextEntry::make('jumlah_pt_peserta')->label('Jumlah PT peserta')->placeholder('—'),
                        TextEntry::make('jumlah_negara_peserta')->label('Jumlah negara')->placeholder('—'),
                        TextEntry::make('negara_peserta')->label('Negara peserta')->placeholder('—'),
                        TextEntry::make('nomor_sertifikat')->label('Nomor sertifikat')->placeholder('—'),
                        TextEntry::make('url_kegiatan')->label('Tautan kegiatan')->placeholder('—')->url(fn (?string $state) => $state)->openUrlInNewTab(),
                        TextEntry::make('deskripsi')->label('Deskripsi')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('catatan_verifikasi')->label('Catatan verifikasi terakhir')->placeholder('—')->columnSpanFull(),
                    ]),
                    Section::make('Tim')->schema([
                        TextEntry::make('anggota_internal')->label('Anggota FKIP')->listWithLineBreaks()->bulleted()
                            ->state(fn (Prestasi $record): array => $record->anggota()->with(['mahasiswa', 'prodi'])->get()
                                ->map(fn ($a) => "{$a->mahasiswa->npm} — {$a->mahasiswa->nama} ({$a->prodi->nama}) · {$a->peran->label()}".($a->is_pengaju ? ' · pengaju' : ''))->all()),
                        TextEntry::make('anggota_luar')->label('Anggota luar FKIP')->listWithLineBreaks()->bulleted()->placeholder('—')
                            ->state(fn (Prestasi $record): array => $record->anggotaEksternal()->get()
                                ->map(fn ($e) => "{$e->nama} — {$e->institusi}".($e->program_studi ? " ({$e->program_studi})" : ''))->all()),
                    ]),
                    Section::make('Dosen pembimbing')->columns(2)->schema([
                        TextEntry::make('pembimbing')->label('Pembimbing')->placeholder('—')
                            ->state(fn (Prestasi $record): ?string => $record->dosenPembimbing !== null ? $record->dosenPembimbing->nama_lengkap : $record->nama_pembimbing_lain),
                        TextEntry::make('status_konfirmasi_pembimbing')->label('Konfirmasi')->badge()
                            ->formatStateUsing(fn (?StatusKonfirmasiPembimbing $state) => $state?->label())->color(fn (?StatusKonfirmasiPembimbing $state) => $state?->warna()),
                        TextEntry::make('catatan_pembimbing')->label('Catatan pembimbing')->placeholder('—')->columnSpanFull(),
                    ]),
                ])->columnSpan(['lg' => 2]),
                Group::make([
                    View::make('filament.admin.prestasi.bukti')->viewData(fn (Prestasi $record): array => self::dataBukti($record)),
                ])->columnSpan(['lg' => 1]),
            ]),
        ]);
    }

    /**
     * Tautan yang boleh dilihat pengguna (Policy) beserta penanda "sudah Anda buka".
     *
     * @return array{tautan: Collection<int, TautanBerkas>, dibuka: list<string>}
     */
    private static function dataBukti(Prestasi $prestasi): array
    {
        $tautan = $prestasi->tautanBukti()->get()->filter(fn (TautanBerkas $t) => Gate::allows('view', $t))->values();
        $dibuka = Aktivitas::where('log_name', 'tautan')->where('event', 'dibuka')->where('causer_id', auth()->id())
            ->whereIn('subject_id', $tautan->pluck('id'))->pluck('subject_id')->unique()->values()->all();

        return ['tautan' => $tautan, 'dibuka' => $dibuka];
    }
}

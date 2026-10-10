<?php

namespace App\Filament\Portal\Resources\PrestasiSayaResource\Schemas;

use App\Actions\Bukti\SimpanTautanBukti;
use App\Actions\Prestasi\SimpanPrestasi;
use App\Enums\BentukPartisipasi;
use App\Enums\BidangPrestasi;
use App\Enums\ModaKegiatan;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Rules\TautanBerkasValid;
use App\Services\OpsiMaster;
use App\Services\Pengaturan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

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
            Section::make('Anggota FKIP')
                ->description('Setiap anggota FKIP mendapat kredit penuh. Anggota dapat berasal dari prodi lain. Pengaju otomatis menjadi anggota.')
                ->visible(fn (Get $get): bool => $get('bentuk') === 'kelompok')
                ->schema([
                    Repeater::make('anggota_internal')->label('Anggota FKIP')->addActionLabel('Tambah anggota')->defaultItems(0)
                        ->columns(2)->schema([
                            Select::make('mahasiswa_id')->label('Mahasiswa')->searchable()->required()->distinct()
                                ->getSearchResultsUsing(fn (string $search): array => self::cariMahasiswa($search))
                                ->getOptionLabelUsing(fn ($value): ?string => self::labelMahasiswa((string) $value))
                                ->helperText('Ketik minimal 2 karakter NPM atau nama.'),
                            Select::make('peran')->label('Peran')->options(['anggota' => 'Anggota', 'ketua' => 'Ketua'])->default('anggota')->required(),
                        ]),
                ]),
            Section::make('Anggota luar FKIP')
                ->description('Dicatat tetapi tidak dihitung dalam statistik FKIP.')
                ->visible(fn (Get $get): bool => $get('bentuk') === 'kelompok')
                ->schema([
                    Repeater::make('anggota_eksternal')->label('Anggota luar FKIP')->addActionLabel('Tambah anggota luar')->defaultItems(0)
                        ->columns(2)->schema([
                            TextInput::make('nama')->label('Nama')->required()->maxLength(150),
                            TextInput::make('nomor_induk')->label('Nomor induk')->maxLength(30),
                            TextInput::make('institusi')->label('Institusi')->required()->maxLength(150),
                            TextInput::make('program_studi')->label('Program studi')->maxLength(150),
                        ]),
                ]),
            Section::make('Dosen pembimbing')->columns(2)->schema([
                Select::make('dosen_pembimbing_id')->label('Dosen pembimbing')->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => self::cariDosen($search))
                    ->getOptionLabelUsing(fn ($value): ?string => self::labelDosen((string) $value))
                    ->helperText('Ketik minimal 2 karakter NIDN atau nama. Dosen akan diminta mengonfirmasi.'),
                TextInput::make('nama_pembimbing_lain')->label('Atau nama pembimbing lain')->maxLength(150)
                    ->helperText('Pembimbing di luar daftar tidak dapat dikonfirmasi lewat sistem.')
                    ->visible(fn (Get $get): bool => blank($get('dosen_pembimbing_id'))),
            ]),
        ]);
    }

    private static function langkahBukti(): Step
    {
        return Step::make('Tautan Bukti & Publikasi')->schema([
            Callout::make('Cara membagikan berkas')
                ->description(new HtmlString(
                    '<ul class="list-disc ps-5 space-y-1">'
                    .'<li>Sertifikat/piagam dan foto penyerahan boleh dibagikan "Siapa saja yang memiliki link".</li>'
                    .'<li>Surat tugas atau dokumen yang memuat NIK/NIP/NPM/nilai: bagikan terbatas ke akun/organisasi kampus (unsil.ac.id) atau akun verifikator.</li>'
                    .'<li>Di aplikasi Google Drive ponsel pilih berkas, lalu "Bagikan" → "Salin link", dan tempel di sini. Tautan ke satu berkas, bukan folder.</li>'
                    .'<li>Gunakan foto tanpa data lokasi (EXIF); sistem tidak dapat membuangnya dari berkas di Drive.</li>'
                    .'</ul>'
                ))->info()->columnSpanFull(),
            Repeater::make('tautan_bukti')->label('Tautan bukti')->addActionLabel('Tambah tautan')->defaultItems(0)
                ->maxItems(SimpanTautanBukti::MAKS_PER_PRESTASI)->columns(2)->schema([
                    Hidden::make('id'),
                    Select::make('jenis')->label('Jenis')->required()->live()
                        ->options(collect(SimpanTautanBukti::JENIS_BUKTI)->mapWithKeys(fn ($j) => [$j->value => $j->label()])),
                    TextInput::make('url')->label('Tautan (https)')->required()->maxLength(2048)->rule(new TautanBerkasValid)
                        ->helperText('Google Drive atau domain unsil.ac.id; bukan pemendek tautan atau folder.'),
                    TextInput::make('label')->label('Keterangan')->maxLength(255),
                    Toggle::make('tampil_publik')->label('Izinkan foto ini tampil di galeri publik')
                        ->helperText('Hanya bila prestasi terverifikasi; berkas di Drive harus dibagikan "Siapa saja yang memiliki link".')
                        ->visible(fn (Get $get): bool => $get('jenis') === 'foto_penyerahan'),
                    Text::make(fn (Get $get): string => filled($get('status_cek'))
                        ? 'Status pemeriksaan: '.$get('status_cek').($get('status_cek') === 'Tidak dapat diakses'
                            ? ' — verifikator mungkin tidak bisa membuka; periksa pengaturan berbagi.' : '')
                        : 'Pemeriksaan keteraksesan dijalankan setelah disimpan.')->color('gray')->columnSpanFull(),
                ]),
        ]);
    }

    /** @return array<string, string> */
    private static function cariMahasiswa(string $kata): array
    {
        if (mb_strlen(trim($kata)) < 2) {
            return [];
        }

        return Mahasiswa::query()->with('prodi')
            ->whereIn('status_mahasiswa', ['aktif', 'cuti'])
            ->where(fn ($q) => $q->where('npm', 'like', "{$kata}%")->orWhere('nama', 'like', "%{$kata}%"))
            ->orderBy('nama')->limit(20)->get()
            ->mapWithKeys(fn (Mahasiswa $m) => [$m->id => "{$m->npm} — {$m->nama} ({$m->prodi?->nama})"])->all();
    }

    private static function labelMahasiswa(string $id): ?string
    {
        $m = Mahasiswa::with('prodi')->find($id);

        return $m === null ? null : "{$m->npm} — {$m->nama} ({$m->prodi?->nama})";
    }

    /** @return array<string, string> */
    private static function cariDosen(string $kata): array
    {
        if (mb_strlen(trim($kata)) < 2) {
            return [];
        }

        return Dosen::query()
            ->where(fn ($q) => $q->where('nidn', 'like', "{$kata}%")->orWhere('nama', 'like', "%{$kata}%"))
            ->orderBy('nama')->limit(20)->get()
            ->mapWithKeys(fn (Dosen $d) => [$d->id => trim(($d->nidn ? "{$d->nidn} — " : '').$d->nama_lengkap)])->all();
    }

    private static function labelDosen(string $id): ?string
    {
        $d = Dosen::find($id);

        return $d === null ? null : trim(($d->nidn ? "{$d->nidn} — " : '').$d->nama_lengkap);
    }

    private static function kodeTingkat(Get $get): ?string
    {
        return filled($get('tingkat_id')) ? app(OpsiMaster::class)->kodeTingkat((string) $get('tingkat_id')) : null;
    }
}

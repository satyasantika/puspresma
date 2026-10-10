<?php

namespace App\Filament\Portal\Pages;

use App\Models\Mahasiswa;
use App\Models\PrestasiAnggota;
use App\Services\Pengaturan;
use App\Support\TahunAkademik;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/** Total poin terverifikasi mahasiswa login per tahun dan per tahun akademik (BR-18, BR-21). */
class PoinSaya extends Page
{
    protected static ?string $slug = 'poin-saya';

    protected static ?string $title = 'Poin Saya';

    protected static ?string $navigationLabel = 'Poin Saya';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    public static function canAccess(): bool
    {
        return (bool) Pengaturan::ambil('poin_aktif', false) && (bool) auth()->user()?->can('prestasi.buat-milik');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.portal.pages.poin-saya')->viewData(fn (): array => $this->data()),
        ]);
    }

    /** @return array{rincian: Collection<int, array<string, mixed>>, perTahun: Collection<int, array{label: string, total: string}>, perTahunAkademik: Collection<int, array{label: string, total: string}>} */
    private function data(): array
    {
        $mahasiswa = Mahasiswa::firstWhere('user_id', auth()->id());
        $awalBulan = (int) Pengaturan::ambil('awal_tahun_akademik_bulan', 8);

        $rincian = $mahasiswa === null ? collect() : PrestasiAnggota::query()
            ->where('mahasiswa_id', $mahasiswa->id)->whereNotNull('poin')
            ->whereHas('prestasi', fn ($q) => $q->terverifikasi())
            ->with(['prestasi.tingkat', 'prestasi.capaian'])->get()
            ->map(fn (PrestasiAnggota $a): array => [
                'kegiatan' => $a->prestasi->nama_kegiatan,
                'selesai' => $a->prestasi->tanggal_selesai,
                'tingkat' => $a->prestasi->tingkat->nama,
                'capaian' => $a->prestasi->capaian->nama,
                'tahun' => $a->prestasi->tahun,
                'tahun_akademik' => TahunAkademik::dari($a->prestasi->tanggal_selesai, $awalBulan),
                'poin' => (string) $a->poin,
            ])->sortByDesc('selesai')->values();

        $jumlahkan = fn (string $kunci): Collection => $rincian->groupBy($kunci)
            ->map(fn (Collection $g, $label) => ['label' => (string) $label, 'total' => $g->reduce(fn (string $c, array $r) => bcadd($c, $r['poin'], 2), '0.00')])
            ->sortByDesc('label')->values();

        return ['rincian' => $rincian, 'perTahun' => $jumlahkan('tahun'), 'perTahunAkademik' => $jumlahkan('tahun_akademik')];
    }
}

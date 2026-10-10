<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\HalamanImpor;
use App\Jobs\ImporDosenDariExcel;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use UnitEnum;

class ImporDosen extends Page implements HasTable
{
    use HalamanImpor;

    protected static ?string $slug = 'impor-dosen';

    protected static ?string $title = 'Impor Dosen';

    protected static ?string $navigationLabel = 'Impor Dosen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|UnitEnum|null $navigationGroup = 'Master';

    protected static function jenisImpor(): string
    {
        return 'dosen';
    }

    protected static function izin(): string
    {
        return 'dosen.impor';
    }

    protected static function kelasJob(): string
    {
        return ImporDosenDariExcel::class;
    }

    protected static function kolomTemplat(): array
    {
        return ['nidn', 'nip', 'nama', 'gelar_depan', 'gelar_belakang', 'kode_prodi', 'email'];
    }

    protected static function contohTemplat(): array
    {
        return [[
            'nidn' => '0400000001', 'nip' => '', 'nama' => 'Contoh Dosen', 'gelar_depan' => 'Dr.', 'gelar_belakang' => 'M.Pd.',
            'kode_prodi' => 'CONTOH-A', 'email' => 'contoh.dosen@'.config('puspresma.domain_pegawai'),
        ]];
    }
}

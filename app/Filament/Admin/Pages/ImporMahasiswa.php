<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\HalamanImpor;
use App\Jobs\ImporMahasiswaDariExcel;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use UnitEnum;

class ImporMahasiswa extends Page implements HasTable
{
    use HalamanImpor;

    protected static ?string $slug = 'impor-mahasiswa';

    protected static ?string $title = 'Impor Mahasiswa';

    protected static ?string $navigationLabel = 'Impor Mahasiswa';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|UnitEnum|null $navigationGroup = 'Master';

    protected static function jenisImpor(): string
    {
        return 'mahasiswa';
    }

    protected static function izin(): string
    {
        return 'mahasiswa.impor';
    }

    protected static function kelasJob(): string
    {
        return ImporMahasiswaDariExcel::class;
    }

    protected static function kolomTemplat(): array
    {
        return ['npm', 'nama', 'kode_prodi', 'angkatan', 'email', 'status_mahasiswa'];
    }

    protected static function contohTemplat(): array
    {
        return [[
            'npm' => '2100000001', 'nama' => 'Contoh Mahasiswa', 'kode_prodi' => 'CONTOH-A', 'angkatan' => '2021',
            'email' => '2100000001@'.config('puspresma.domain_mahasiswa'), 'status_mahasiswa' => 'aktif',
        ]];
    }
}

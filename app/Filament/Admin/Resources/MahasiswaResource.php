<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MahasiswaResource\Pages\CreateMahasiswa;
use App\Filament\Admin\Resources\MahasiswaResource\Pages\EditMahasiswa;
use App\Filament\Admin\Resources\MahasiswaResource\Pages\ListMahasiswa;
use App\Filament\Admin\Resources\MahasiswaResource\Pages\ViewMahasiswa;
use App\Filament\Admin\Resources\MahasiswaResource\Schemas\MahasiswaForm;
use App\Filament\Admin\Resources\MahasiswaResource\Tables\MahasiswaTable;
use App\Models\Mahasiswa;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Data master mahasiswa; admin-prodi dibatasi ke prodinya (tabel) dan Policy (per record). */
class MahasiswaResource extends Resource
{
    protected static ?string $model = Mahasiswa::class;

    protected static ?string $slug = 'mahasiswa';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $modelLabel = 'mahasiswa';

    protected static ?string $pluralModelLabel = 'Mahasiswa';

    protected static ?string $navigationLabel = 'Mahasiswa';

    protected static string|UnitEnum|null $navigationGroup = 'Master';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return MahasiswaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MahasiswaTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMahasiswa::route('/'),
            'create' => CreateMahasiswa::route('/create'),
            'view' => ViewMahasiswa::route('/{record}'),
            'edit' => EditMahasiswa::route('/{record}/edit'),
        ];
    }
}

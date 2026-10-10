<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DosenResource\Pages\CreateDosen;
use App\Filament\Admin\Resources\DosenResource\Pages\EditDosen;
use App\Filament\Admin\Resources\DosenResource\Pages\ListDosen;
use App\Filament\Admin\Resources\DosenResource\Pages\ViewDosen;
use App\Filament\Admin\Resources\DosenResource\Schemas\DosenForm;
use App\Filament\Admin\Resources\DosenResource\Tables\DosenTable;
use App\Models\Dosen;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Data master dosen; admin-prodi dibatasi ke prodinya (tabel) dan Policy (per record). */
class DosenResource extends Resource
{
    protected static ?string $model = Dosen::class;

    protected static ?string $slug = 'dosen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $modelLabel = 'dosen';

    protected static ?string $pluralModelLabel = 'Dosen';

    protected static ?string $navigationLabel = 'Dosen';

    protected static string|UnitEnum|null $navigationGroup = 'Master';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return DosenForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DosenTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDosen::route('/'),
            'create' => CreateDosen::route('/create'),
            'view' => ViewDosen::route('/{record}'),
            'edit' => EditDosen::route('/{record}/edit'),
        ];
    }
}

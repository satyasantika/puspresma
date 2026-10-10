<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PrestasiResource\Pages\CreatePrestasi;
use App\Filament\Admin\Resources\PrestasiResource\Pages\EditPrestasi;
use App\Filament\Admin\Resources\PrestasiResource\Pages\ListPrestasi;
use App\Filament\Admin\Resources\PrestasiResource\Pages\ViewPrestasi;
use App\Filament\Admin\Resources\PrestasiResource\RelationManagers\KandidatDuplikatRelationManager;
use App\Filament\Admin\Resources\PrestasiResource\RelationManagers\RiwayatStatusRelationManager;
use App\Filament\Admin\Resources\PrestasiResource\RelationManagers\SnapshotTautanRelationManager;
use App\Filament\Admin\Resources\PrestasiResource\Schemas\PrestasiInfolist;
use App\Filament\Admin\Resources\PrestasiResource\Tables\PrestasiTable;
use App\Filament\Shared\PrestasiForm;
use App\Models\Prestasi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Antrean dan pengelolaan prestasi di panel admin; otorisasi lewat PrestasiPolicy, cakupan prodi di tabel (BR-15). */
class PrestasiResource extends Resource
{
    protected static ?string $model = Prestasi::class;

    protected static ?string $slug = 'prestasi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $modelLabel = 'prestasi';

    protected static ?string $pluralModelLabel = 'Prestasi';

    protected static ?string $navigationLabel = 'Prestasi';

    protected static string|UnitEnum|null $navigationGroup = 'Prestasi';

    protected static ?string $recordTitleAttribute = 'nama_kegiatan';

    public static function form(Schema $schema): Schema
    {
        return PrestasiForm::configure($schema, admin: true);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PrestasiInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrestasiTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [KandidatDuplikatRelationManager::class, RiwayatStatusRelationManager::class, SnapshotTautanRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrestasi::route('/'),
            'create' => CreatePrestasi::route('/create'),
            'view' => ViewPrestasi::route('/{record}'),
            'edit' => EditPrestasi::route('/{record}/edit'),
        ];
    }
}

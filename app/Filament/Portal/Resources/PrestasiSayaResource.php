<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\PrestasiSayaResource\Pages\CreatePrestasiSaya;
use App\Filament\Portal\Resources\PrestasiSayaResource\Pages\EditPrestasiSaya;
use App\Filament\Portal\Resources\PrestasiSayaResource\Pages\ListPrestasiSaya;
use App\Filament\Portal\Resources\PrestasiSayaResource\Pages\ViewPrestasiSaya;
use App\Filament\Portal\Resources\PrestasiSayaResource\Tables\PrestasiSayaTable;
use App\Filament\Shared\PrestasiForm;
use App\Models\Prestasi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/** Prestasi milik mahasiswa login (pengaju atau anggota); otorisasi lewat App\Policies\PrestasiPolicy. */
class PrestasiSayaResource extends Resource
{
    protected static ?string $model = Prestasi::class;

    protected static ?string $slug = 'prestasi-saya';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $modelLabel = 'prestasi';

    protected static ?string $pluralModelLabel = 'Prestasi Saya';

    protected static ?string $navigationLabel = 'Prestasi Saya';

    protected static ?string $recordTitleAttribute = 'nama_kegiatan';

    public static function form(Schema $schema): Schema
    {
        return PrestasiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrestasiSayaTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrestasiSaya::route('/'),
            'create' => CreatePrestasiSaya::route('/create'),
            'view' => ViewPrestasiSaya::route('/{record}'),
            'edit' => EditPrestasiSaya::route('/{record}/edit'),
        ];
    }
}

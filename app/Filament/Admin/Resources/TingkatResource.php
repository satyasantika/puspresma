<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TingkatResource\Pages\ManageTingkat;
use App\Filament\Admin\Support\TabelMaster;
use App\Models\Tingkat;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/** Master tingkat; otorisasi lewat App\Policies\MasterPolicy. */
class TingkatResource extends Resource
{
    protected static ?string $model = Tingkat::class;

    protected static ?string $slug = 'tingkat';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static ?string $modelLabel = 'tingkat';

    protected static ?string $pluralModelLabel = 'Tingkat';

    protected static ?string $navigationLabel = 'Tingkat';

    protected static string|UnitEnum|null $navigationGroup = 'Master';

    protected static ?string $recordTitleAttribute = 'nama';

    /** Menu Master hanya untuk pengelola master; peran lain tetap bisa membuka (baca saja) lewat URL. */
    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->can('master.kelola');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('kode')->label('Kode')->required()->maxLength(30)
                ->unique(ignoreRecord: true),
            TextInput::make('nama')->label('Nama')->required()->maxLength(100),
            TextInput::make('urutan')->label('Urutan (tingkat makin tinggi, angka makin besar)')->numeric()->integer()->default(0),
            TextInput::make('kode_simkatmawa')->label('Kode SIMKATMAWA')->maxLength(50)->helperText(TabelMaster::CATATAN_SIMKATMAWA),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return TabelMaster::lengkapi($table
            ->defaultSort('urutan')
            ->columns([
                TextColumn::make('urutan')->label('#')->sortable(),
                TextColumn::make('kode')->label('Kode'),
                TextColumn::make('nama')->label('Nama')->searchable(),
                TextColumn::make('kode_simkatmawa')->label('Kode SIMKATMAWA')->placeholder('—'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ]));
    }

    public static function getPages(): array
    {
        return ['index' => ManageTingkat::route('/')];
    }
}

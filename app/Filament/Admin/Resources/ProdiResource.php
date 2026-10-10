<?php

namespace App\Filament\Admin\Resources;

use App\Enums\JenjangProdi;
use App\Filament\Admin\Resources\ProdiResource\Pages\ManageProgramStudi;
use App\Filament\Admin\Support\TabelMaster;
use App\Models\Prodi;
use BackedEnum;
use Filament\Forms\Components\Select;
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

/** Master program studi; otorisasi lewat App\Policies\MasterPolicy. */
class ProdiResource extends Resource
{
    protected static ?string $model = Prodi::class;

    protected static ?string $slug = 'prodi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::AcademicCap;

    protected static ?string $modelLabel = 'program studi';

    protected static ?string $pluralModelLabel = 'Program Studi';

    protected static ?string $navigationLabel = 'Program Studi';

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
            TextInput::make('kode')->label('Kode')->required()->maxLength(20)
                ->unique(ignoreRecord: true),
            TextInput::make('kode_pddikti')->label('Kode PDDikti')->maxLength(20),
            TextInput::make('nama')->label('Nama')->required()->maxLength(150),
            Select::make('jenjang')->label('Jenjang')->options(collect(JenjangProdi::cases())->mapWithKeys(fn ($j) => [$j->value => $j->label()]))->required(),
            TextInput::make('kode_eksternal')->label('Kode eksternal')->maxLength(50),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return TabelMaster::lengkapi($table
            ->defaultSort('nama')
            ->columns([
                TextColumn::make('kode')->label('Kode')->searchable()->sortable(),
                TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
                TextColumn::make('jenjang')->label('Jenjang')->formatStateUsing(fn ($state) => $state?->label())->badge(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ]));
    }

    public static function getPages(): array
    {
        return ['index' => ManageProgramStudi::route('/')];
    }
}

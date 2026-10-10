<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\KategoriLombaResource\Pages\ManageKategoriLomba;
use App\Filament\Admin\Support\TabelMaster;
use App\Models\KategoriLomba;
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

/** Master kategori lomba; otorisasi lewat App\Policies\MasterPolicy. */
class KategoriLombaResource extends Resource
{
    protected static ?string $model = KategoriLomba::class;

    protected static ?string $slug = 'kategori-lomba';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Tag;

    protected static ?string $modelLabel = 'kategori lomba';

    protected static ?string $pluralModelLabel = 'Kategori Lomba';

    protected static ?string $navigationLabel = 'Kategori Lomba';

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
            Select::make('jenis_kegiatan_id')->label('Jenis kegiatan')
                ->relationship('jenisKegiatan', 'nama')->placeholder('Semua jenis kegiatan')
                ->helperText('Kosongkan bila berlaku untuk semua jenis kegiatan.'),
            TextInput::make('nama')->label('Nama')->required()->maxLength(100),
            TextInput::make('kode_simkatmawa')->label('Kode SIMKATMAWA')->maxLength(50)->helperText(TabelMaster::CATATAN_SIMKATMAWA),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return TabelMaster::lengkapi($table
            ->defaultSort('nama')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('jenisKegiatan'))
            ->columns([
                TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
                TextColumn::make('jenisKegiatan.nama')->label('Jenis kegiatan')->placeholder('Semua jenis'),
                TextColumn::make('kode_simkatmawa')->label('Kode SIMKATMAWA')->placeholder('—'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ]));
    }

    public static function getPages(): array
    {
        return ['index' => ManageKategoriLomba::route('/')];
    }
}

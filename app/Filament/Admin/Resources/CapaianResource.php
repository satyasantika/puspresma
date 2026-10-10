<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CapaianResource\Pages\ManageCapaian;
use App\Filament\Admin\Support\TabelMaster;
use App\Models\Capaian;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/** Master capaian; otorisasi lewat App\Policies\MasterPolicy. */
class CapaianResource extends Resource
{
    protected static ?string $model = Capaian::class;

    protected static ?string $slug = 'capaian';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Star;

    protected static ?string $modelLabel = 'capaian';

    protected static ?string $pluralModelLabel = 'Capaian';

    protected static ?string $navigationLabel = 'Capaian';

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
            Select::make('jenis_kegiatan_id')->label('Jenis kegiatan')->relationship('jenisKegiatan', 'nama')->required(),
            TextInput::make('kode')->label('Kode')->required()->maxLength(40)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule, Get $get) => $rule->where('jenis_kegiatan_id', $get('jenis_kegiatan_id'))),
            TextInput::make('nama')->label('Nama')->required()->maxLength(100),
            Toggle::make('is_juara')->label('Termasuk juara')->default(false),
            TextInput::make('urutan')->label('Urutan')->numeric()->integer()->default(0),
            TextInput::make('kode_simkatmawa')->label('Kode SIMKATMAWA')->maxLength(50)->helperText(TabelMaster::CATATAN_SIMKATMAWA),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return TabelMaster::lengkapi($table
            ->defaultSort('urutan')
            ->reorderable('urutan', (bool) auth()->user()?->can('master.kelola'))
            ->modifyQueryUsing(fn (Builder $query) => $query->with('jenisKegiatan'))
            ->columns([
                TextColumn::make('jenisKegiatan.nama')->label('Jenis kegiatan'),
                TextColumn::make('kode')->label('Kode'),
                TextColumn::make('nama')->label('Nama')->searchable(),
                IconColumn::make('is_juara')->label('Juara')->boolean(),
                TextColumn::make('kode_simkatmawa')->label('Kode SIMKATMAWA')->placeholder('—'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ]), [SelectFilter::make('jenis_kegiatan_id')->label('Jenis kegiatan')->relationship('jenisKegiatan', 'nama')]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCapaian::route('/')];
    }
}

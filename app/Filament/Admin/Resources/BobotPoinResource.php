<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BobotPoinResource\Pages\ManageBobotPoin;
use App\Models\BobotPoin;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Bobot poin per jenis × tingkat × capaian; otorisasi lewat BobotPoinPolicy (poin.kelola). */
class BobotPoinResource extends Resource
{
    protected static ?string $model = BobotPoin::class;

    protected static ?string $slug = 'bobot-poin';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?string $modelLabel = 'bobot poin';

    protected static ?string $pluralModelLabel = 'Bobot Poin';

    protected static ?string $navigationLabel = 'Bobot Poin';

    protected static string|UnitEnum|null $navigationGroup = 'Poin';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('jenis_kegiatan_id')->label('Jenis kegiatan')->relationship('jenisKegiatan', 'nama')->required()->live(),
            Select::make('tingkat_id')->label('Tingkat')->relationship('tingkat', 'nama')->required(),
            Select::make('capaian_id')->label('Capaian')
                ->relationship('capaian', 'nama', fn (Builder $query, $get) => $query->where('jenis_kegiatan_id', $get('jenis_kegiatan_id')))
                ->required(),
            TextInput::make('poin')->label('Poin')->numeric()->minValue(0)->maxValue(999999.99)->step(0.01)->required(),
            DatePicker::make('berlaku_mulai')->label('Berlaku mulai')->required(),
            DatePicker::make('berlaku_sampai')->label('Berlaku sampai')->helperText('Kosong = masih berlaku.')->afterOrEqual('berlaku_mulai'),
            TextInput::make('dasar_penetapan')->label('Dasar penetapan')->maxLength(255)->helperText('Mis. nomor SK Dekan.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['jenisKegiatan', 'tingkat', 'capaian']))
            ->defaultSort('berlaku_mulai', 'desc')
            ->columns([
                TextColumn::make('jenisKegiatan.nama')->label('Jenis'),
                TextColumn::make('tingkat.nama')->label('Tingkat'),
                TextColumn::make('capaian.nama')->label('Capaian'),
                TextColumn::make('poin')->label('Poin')->alignEnd(),
                TextColumn::make('berlaku_mulai')->label('Mulai')->date('d M Y')->sortable(),
                TextColumn::make('berlaku_sampai')->label('Sampai')->date('d M Y')->placeholder('berlaku'),
                TextColumn::make('dasar_penetapan')->label('Dasar')->limit(30)->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('jenis_kegiatan_id')->label('Jenis kegiatan')->relationship('jenisKegiatan', 'nama'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageBobotPoin::route('/')];
    }
}

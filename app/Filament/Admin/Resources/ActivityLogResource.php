<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Filament\Admin\Resources\ActivityLogResource\Pages\ViewActivityLog;
use App\Filament\Admin\Resources\ActivityLogResource\Tables\ActivityLogTable;
use App\Models\Aktivitas;
use BackedEnum;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Jejak audit hanya-baca (permission audit.lihat, lewat AktivitasPolicy). */
class ActivityLogResource extends Resource
{
    protected static ?string $model = Aktivitas::class;

    protected static ?string $slug = 'log-aktivitas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'log aktivitas';

    protected static ?string $pluralModelLabel = 'Log Aktivitas';

    protected static ?string $navigationLabel = 'Log Aktivitas';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->label('Waktu')->dateTime('d M Y H:i:s'),
            TextEntry::make('causer.name')->label('Pengguna')->placeholder('sistem'),
            TextEntry::make('log_name')->label('Log')->badge(),
            TextEntry::make('event')->label('Peristiwa')->badge(),
            TextEntry::make('description')->label('Deskripsi'),
            TextEntry::make('subject_type')->label('Subjek')
                ->formatStateUsing(fn (?string $state, Aktivitas $record) => $state ? class_basename($state).' '.$record->subject_id : '—'),
            KeyValueEntry::make('nilai_lama')->label('Nilai lama')
                ->state(fn (Aktivitas $record): array => self::ratakan($record->attribute_changes['old'] ?? []))
                ->visible(fn (Aktivitas $record): bool => ! empty($record->attribute_changes['old'] ?? [])),
            KeyValueEntry::make('nilai_baru')->label('Nilai baru')
                ->state(fn (Aktivitas $record): array => self::ratakan($record->attribute_changes['attributes'] ?? []))
                ->visible(fn (Aktivitas $record): bool => ! empty($record->attribute_changes['attributes'] ?? [])),
            KeyValueEntry::make('rincian')->label('Properti')
                ->state(fn (Aktivitas $record): array => self::ratakan($record->properties?->toArray() ?? []))
                ->visible(fn (Aktivitas $record): bool => ! empty($record->properties?->toArray())),
        ])->columns(2);
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<string, string>
     */
    private static function ratakan(array $data): array
    {
        return collect($data)
            ->map(fn ($nilai) => is_scalar($nilai) || $nilai === null
                ? (string) ($nilai ?? '—')
                : json_encode($nilai, JSON_UNESCAPED_UNICODE))
            ->all();
    }

    public static function table(Table $table): Table
    {
        return ActivityLogTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
            'view' => ViewActivityLog::route('/{record}'),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\ActivityLogResource\Tables;

use App\Models\Aktivitas;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['causer', 'subject']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i:s')->sortable(),
                TextColumn::make('causer.name')->label('Pengguna')->placeholder('sistem'),
                TextColumn::make('log_name')->label('Log')->badge()->searchable(),
                TextColumn::make('description')->label('Deskripsi')->searchable()->wrap(),
                TextColumn::make('subject_type')->label('Subjek')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('log_name')->label('Log')
                    ->options(fn () => Aktivitas::query()->distinct()->orderBy('log_name')->pluck('log_name', 'log_name')->all()),
                Filter::make('tanggal')
                    ->schema([
                        DatePicker::make('dari')->label('Dari tanggal'),
                        DatePicker::make('sampai')->label('Sampai tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['dari'] ?? null, fn (Builder $q, string $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['sampai'] ?? null, fn (Builder $q, string $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->recordActions([
                ViewAction::make()->label('Detail'),
            ])
            ->toolbarActions([]);
    }
}

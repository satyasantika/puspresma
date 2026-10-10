<?php

namespace App\Filament\Admin\Support;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

/** Bagian tabel yang sama untuk Resource master sederhana (CRUD modal + soft delete). */
class TabelMaster
{
    public const CATATAN_SIMKATMAWA = 'Isi sesuai kode/label SIMKATMAWA (perlu verifikasi).';

    /** @param  list<BaseFilter>  $filterTambahan */
    public static function lengkapi(Table $table, array $filterTambahan = []): Table
    {
        return $table
            ->filters([...$filterTambahan, TrashedFilter::make()->label('Data terhapus')])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make()]),
            ]);
    }
}

<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Actions\Pengguna\HapusPengguna;
use App\Actions\Pengguna\SimpanPengguna;
use App\Filament\Admin\Resources\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $user */
        $user = $this->record;
        $data['peran'] = $user->roles->pluck('name')->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        return app(SimpanPengguna::class)->jalankan($record, $data, auth()->user());
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->using(fn (User $record) => app(HapusPengguna::class)->jalankan($record, auth()->user())),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\DosenResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DosenForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas')->columns(2)->schema([
                TextInput::make('nidn')->label('NIDN')->maxLength(20)->unique(ignoreRecord: true),
                TextInput::make('nip')->label('NIP')->maxLength(30)->unique(ignoreRecord: true),
                TextInput::make('gelar_depan')->label('Gelar depan')->maxLength(30),
                TextInput::make('nama')->label('Nama')->required()->maxLength(150),
                TextInput::make('gelar_belakang')->label('Gelar belakang')->maxLength(60),
                Select::make('prodi_id')->label('Program studi')->relationship('prodi', 'nama')->searchable()->preload(),
                TextInput::make('kode_eksternal')->label('Kode eksternal')->maxLength(50),
            ]),
            Section::make('Kontak (data pribadi)')->visible(fn (): bool => (bool) auth()->user()?->can('master.kelola'))->schema([
                TextInput::make('email')->label('Surel')->email()->maxLength(191)->unique(ignoreRecord: true),
            ]),
        ]);
    }
}

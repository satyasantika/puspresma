<?php

namespace App\Filament\Admin\Resources\MahasiswaResource\Schemas;

use App\Enums\StatusMahasiswa;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MahasiswaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas')->columns(2)->schema([
                TextInput::make('npm')->label('NPM')->required()->regex('/^\d{8,20}$/')->maxLength(20)
                    ->unique(ignoreRecord: true),
                TextInput::make('nama')->label('Nama')->required()->maxLength(150),
                Select::make('prodi_id')->label('Program studi')->relationship('prodi', 'nama')->searchable()->preload()->required(),
                TextInput::make('angkatan')->label('Angkatan')->numeric()->integer()->required()
                    ->minValue(1990)->maxValue((int) date('Y')),
                Select::make('status_mahasiswa')->label('Status')
                    ->options(collect(StatusMahasiswa::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->default(StatusMahasiswa::Aktif->value)->required(),
                TextInput::make('kode_eksternal')->label('Kode eksternal')->maxLength(50),
            ]),
            Section::make('Kontak (data pribadi)')->columns(2)->visible(fn (): bool => (bool) auth()->user()?->can('master.kelola'))->schema([
                TextInput::make('email')->label('Surel')->email()->maxLength(191)->unique(ignoreRecord: true),
                TextInput::make('nomor_hp')->label('Nomor HP')->tel()->maxLength(20),
            ]),
        ]);
    }
}

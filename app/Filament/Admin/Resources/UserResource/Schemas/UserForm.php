<?php

namespace App\Filament\Admin\Resources\UserResource\Schemas;

use App\Models\Prodi;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $peran = array_keys(PeranDanIzinSeeder::PERAN);

        return $schema->components([
            Section::make('Akun')->columns(2)->schema([
                TextInput::make('name')->label('Nama')->required()->maxLength(255),
                TextInput::make('email')->label('Surel')->email()->required()->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')->label('Kata sandi')->password()->revealable()
                    ->rule(Password::min(12)->letters()->numbers())
                    ->hiddenOn('view')
                    ->helperText('Kosongkan saat membuat akun: kata sandi acak dibuat dan tautan atur kata sandi dikirim lewat surel.'),
                Toggle::make('is_active')->label('Aktif')->default(true),
                Toggle::make('wajib_ganti_sandi')->label('Wajib ganti kata sandi')->default(false)
                    ->helperText('Pengguna diarahkan ke halaman ganti kata sandi pada akses berikutnya. Disarankan bila kata sandi diberikan oleh admin.'),
            ]),
            Section::make('Identitas')->columns(2)->schema([
                TextInput::make('npm')->label('NPM')->maxLength(20),
                TextInput::make('nidn')->label('NIDN')->maxLength(20),
                TextInput::make('nip')->label('NIP')->maxLength(30),
                Select::make('prodi_id')->label('Program studi')->placeholder('—')->searchable()
                    ->options(fn () => Prodi::orderBy('nama')->pluck('nama', 'id')->all())
                    ->helperText('Wajib untuk admin-prodi: menentukan prestasi yang dapat dilihat dan diverifikasi.'),
                TextInput::make('nomor_wa')->label('Nomor WhatsApp')->tel()->maxLength(20)
                    ->helperText('Data pribadi; hanya untuk admin-fakultas dan super-admin.'),
            ]),
            Section::make('Hak akses')->schema([
                CheckboxList::make('peran')->label('Peran')
                    ->options(array_combine($peran, $peran))
                    ->columns(3),
            ]),
        ]);
    }
}

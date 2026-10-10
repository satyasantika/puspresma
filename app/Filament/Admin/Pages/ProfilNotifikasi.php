<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Shared\Concerns\HalamanProfilNotifikasi;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/** Preferensi notifikasi WhatsApp pengguna login. */
class ProfilNotifikasi extends Page
{
    use HalamanProfilNotifikasi;

    protected static ?string $slug = 'profil-notifikasi';

    protected static ?string $title = 'Preferensi Notifikasi';

    protected static ?string $navigationLabel = 'Preferensi Notifikasi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?int $navigationSort = 90;
}

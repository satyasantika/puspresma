<?php

namespace App\Filament\Pages\Auth;

use App\Actions\Pengguna\GantiSandi;
use App\Actions\Pengguna\UbahProfil;
use App\Models\User;
use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;

/** Profil bersama kedua panel: ubah nama/nomor WhatsApp dan ganti kata sandi (sesi lain otomatis keluar). */
class EditProfil extends EditProfile
{
    private bool $baruGantiSandi = false;

    /** Tampil di dalam panel (dengan menu), bukan kartu sempit, agar formulir dua kolom terbaca. */
    public static function isSimple(): bool
    {
        return false;
    }

    public function defaultForm(Schema $schema): Schema
    {
        return parent::defaultForm($schema)->inlineLabel(false);
    }

    public function getMaxContentWidth(): Width
    {
        return Width::FiveExtraLarge;
    }

    public function form(Schema $schema): Schema
    {
        $wajib = (bool) $this->getUser()->getAttributeValue('wajib_ganti_sandi');

        return $schema->components([
            Section::make('Profil')->columns(2)->schema([
                TextInput::make('name')->label('Nama')->required()->maxLength(255),
                TextInput::make('email')->label('Surel')->disabled()->dehydrated(false)
                    ->helperText('Surel tertaut ke data induk kampus. Hubungi admin bila perlu diubah.'),
                TextInput::make('nomor_wa')->label('Nomor WhatsApp')->tel()->maxLength(20)
                    ->helperText('Hanya terlihat oleh admin fakultas dan Anda sendiri.'),
            ]),
            Section::make('Ganti kata sandi')
                ->description($wajib
                    ? 'Administrator mewajibkan Anda mengganti kata sandi sebelum melanjutkan.'
                    : 'Setelah diganti, semua perangkat lain otomatis keluar. Kosongkan bila tidak ingin mengganti.')
                ->columns(1)->schema([
                    TextInput::make('sandi_lama')->label('Kata sandi saat ini')->password()->revealable()
                        ->autocomplete('current-password')->dehydrated(true)
                        ->required(fn ($get): bool => $wajib || filled($get('sandi_baru'))),
                    TextInput::make('sandi_baru')->label('Kata sandi baru')->password()->revealable()
                        ->autocomplete('new-password')->live(debounce: 500)
                        ->helperText('Minimal 12 karakter, memuat huruf dan angka.')
                        ->required($wajib),
                    TextInput::make('sandi_baru_konfirmasi')->label('Ulangi kata sandi baru')->password()->revealable()
                        ->autocomplete('new-password')->same('sandi_baru')
                        ->required(fn ($get): bool => filled($get('sandi_baru'))),
                ]),
        ]);
    }

    /** @param  array<string, mixed>  $data */
    protected function handleRecordUpdate(Model $record, #[SensitiveParameter] array $data): Model
    {
        /** @var User $record */
        app(UbahProfil::class)->jalankan($record, $data);

        if (filled($data['sandi_baru'] ?? null)) {
            app(GantiSandi::class)->jalankan($record, (string) ($data['sandi_lama'] ?? ''), (string) $data['sandi_baru']);
            $this->baruGantiSandi = true;
        }

        return $record;
    }

    protected function afterSave(): void
    {
        $this->data['sandi_lama'] = $this->data['sandi_baru'] = $this->data['sandi_baru_konfirmasi'] = null;
    }

    protected function getSavedNotification(): ?FilamentNotification
    {
        return FilamentNotification::make()->success()
            ->title($this->baruGantiSandi ? 'Kata sandi diganti. Perangkat lain telah dikeluarkan.' : 'Profil disimpan');
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->baruGantiSandi ? Filament::getUrl() : null;
    }
}

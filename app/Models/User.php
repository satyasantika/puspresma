<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\TercatatAktivitas;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string|null $app_authentication_secret
 * @property array<string>|null $app_authentication_recovery_codes
 */
#[Fillable(['name', 'email', 'password', 'npm', 'nidn', 'nip', 'prodi_id', 'google_id', 'nomor_wa', 'notif_wa_aktif', 'is_active'])]
#[Hidden(['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasUuids, Notifiable, TercatatAktivitas;

    protected function namaLog(): string
    {
        return 'pengguna';
    }

    /** @return list<string> */
    protected function atributTercatat(): array
    {
        return ['name', 'email', 'is_active'];
    }

    /** @return BelongsTo<Prodi, $this> */
    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    /** @return HasOne<Mahasiswa, $this> */
    public function mahasiswa(): HasOne
    {
        return $this->hasOne(Mahasiswa::class);
    }

    /** @return HasOne<Dosen, $this> */
    public function dosen(): HasOne
    {
        return $this->hasOne(Dosen::class);
    }

    public function wajibMfa(): bool
    {
        return $this->hasAnyRole(config('puspresma.peran_wajib_mfa'));
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(?string $secret): void
    {
        $this->app_authentication_secret = $secret;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    /** @return ?array<string> */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    /** @param  ?array<string>  $codes */
    public function saveAppAuthenticationRecoveryCodes(?array $codes): void
    {
        $this->app_authentication_recovery_codes = $codes;
        $this->save();
    }

    protected $attributes = ['is_active' => true, 'notif_wa_aktif' => false];

    /** Peran yang boleh membuka tiap panel Filament. */
    public const PERAN_PANEL = [
        'admin' => ['super-admin', 'admin-fakultas', 'admin-prodi', 'pimpinan'],
        'portal' => ['mahasiswa', 'dosen'],
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active
            && $this->hasAnyRole(self::PERAN_PANEL[$panel->getId()] ?? []);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notif_wa_aktif' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }
}

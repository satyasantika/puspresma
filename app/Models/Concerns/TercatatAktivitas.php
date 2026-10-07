<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Jejak audit server-side. Pelaku selalu auth()->user() (bawaan activitylog);
 * jangan memanggil causedBy() dengan nilai dari input pengguna.
 */
trait TercatatAktivitas
{
    use LogsActivity;

    /** @var list<string> Atribut yang tidak pernah boleh masuk log. */
    protected static array $dikecualikanDariLog = [
        'password', 'remember_token', 'token', 'api_token',
        'app_authentication_secret', 'app_authentication_recovery_codes',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        $atribut = array_values(array_diff($this->atributTercatat(), static::$dikecualikanDariLog, $this->getHidden()));

        return LogOptions::defaults()
            ->logOnly($atribut)
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName($this->namaLog());
    }

    /** @return list<string> */
    abstract protected function atributTercatat(): array;

    protected function namaLog(): string
    {
        return Str::kebab(class_basename($this));
    }
}

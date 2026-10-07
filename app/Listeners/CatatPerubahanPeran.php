<?php

namespace App\Listeners;

use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;

/** Mencatat penambahan/pencabutan peran pengguna ke activitylog (log_name 'pengguna'). */
class CatatPerubahanPeran
{
    public function handle(RoleAttachedEvent|RoleDetachedEvent $event): void
    {
        $ditambah = $event instanceof RoleAttachedEvent;
        $nama = $this->namaPeran($event->rolesOrIds);

        if ($nama === []) {
            return;
        }

        activity('pengguna')
            ->event($ditambah ? 'peran_ditambah' : 'peran_dicabut')
            ->performedOn($event->model)
            ->withProperties(['peran' => $nama])
            ->log($ditambah ? 'Peran ditambahkan' : 'Peran dicabut');
    }

    /** @return list<string> */
    private function namaPeran(mixed $rolesOrIds): array
    {
        return collect(is_iterable($rolesOrIds) ? $rolesOrIds : [$rolesOrIds])
            ->map(fn ($r) => $r instanceof Model ? $r->getAttribute('name') : Role::find($r)?->name)
            ->filter()
            ->values()
            ->all();
    }
}

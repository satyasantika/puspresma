<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

class CatatLoginTerakhir
{
    public function handle(Login $event): void
    {
        if ($event->user instanceof User && ! request()->attributes->get('tanpa_catat_login')) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
        }
    }
}

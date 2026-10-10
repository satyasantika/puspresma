<?php

namespace App\Events;

use App\Models\Mahasiswa;
use App\Models\Prestasi;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/** Anggota baru masuk tim prestasi (listener notifikasi dibuat di F7.1). */
class AnggotaTimDitambahkan
{
    use Dispatchable;

    public function __construct(public Prestasi $prestasi, public Mahasiswa $anggota, public User $olehPengguna) {}
}

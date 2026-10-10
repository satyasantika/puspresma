<?php

namespace App\Actions\Bukti;

use App\Models\Prestasi;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Rules\TautanBerkasValid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Mengganti tautan pada prestasi terverifikasi (BR-30): hanya admin-fakultas, catatan wajib, snapshot 'penggantian'. */
class GantiTautanTerverifikasi
{
    public function __construct(private readonly SimpanSnapshotTautan $snapshot) {}

    public function jalankan(Prestasi $prestasi, TautanBerkas $tautan, string $urlBaru, string $catatan, User $pelaku): TautanBerkas
    {
        Gate::forUser($pelaku)->authorize('gantiTautan', $prestasi);

        if (! $tautan->pemilik()->is($prestasi)) {
            throw ValidationException::withMessages(['tautan' => 'Tautan bukan milik prestasi ini.']);
        }

        if (mb_strlen(trim($catatan)) < 10) {
            throw ValidationException::withMessages(['catatan' => 'Catatan wajib diisi minimal 10 karakter.']);
        }

        $urlBaru = trim($urlBaru);
        Validator::make(['url' => $urlBaru], ['url' => [new TautanBerkasValid]], attributes: ['url' => 'tautan'])->validate();

        return DB::transaction(function () use ($prestasi, $tautan, $urlBaru, $catatan, $pelaku): TautanBerkas {
            $lama = $tautan->url;
            $tautan->update(['url' => $urlBaru]);

            activity('tautan')->event('diganti')->performedOn($tautan)
                ->withProperties(['url_lama' => $lama, 'url_baru' => $urlBaru, 'catatan' => trim($catatan)])
                ->log('Tautan prestasi terverifikasi diganti');

            $this->snapshot->jalankan($prestasi, $pelaku, 'penggantian', $tautan);

            return $tautan;
        });
    }
}

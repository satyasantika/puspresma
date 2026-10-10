<?php

namespace App\Services\Berkas;

use App\Contracts\PenyimpananBerkas;
use App\Enums\JenisTautan;
use App\Enums\PenyediaTautan;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Rules\TautanBerkasValid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Implementasi mode 'tautan': hanya menyimpan URL + metadata. */
class TautanEksternal implements PenyimpananBerkas
{
    public function simpanTautan(Model $pemilik, JenisTautan $jenis, string $url, ?string $label, User $pengguna): TautanBerkas
    {
        $url = trim($url);
        Validator::make(['url' => $url], ['url' => [new TautanBerkasValid]], attributes: ['url' => 'tautan'])->validate();

        $kunciLimit = 'simpan-tautan:'.$pengguna->getKey();
        if (RateLimiter::tooManyAttempts($kunciLimit, 60)) {
            throw ValidationException::withMessages(['url' => 'Terlalu banyak tautan disimpan; coba lagi nanti.']);
        }
        RateLimiter::hit($kunciLimit, 3600);

        // penyedia, drive_file_id, dan url_normal diturunkan oleh TautanBerkasObserver::saving.
        return $pemilik->getConnection()->transaction(fn () => TautanBerkas::create([
            'pemilik_type' => $pemilik->getMorphClass(),
            'pemilik_id' => $pemilik->getKey(),
            'jenis' => $jenis,
            'label' => filled($label) ? $label : null,
            'url' => $url,
            'ditambahkan_oleh' => $pengguna->getKey(),
        ]));
    }

    public function urlBuka(TautanBerkas $tautan): string
    {
        return $tautan->url;
    }

    public function urlPratinjau(TautanBerkas $tautan): ?string
    {
        return $tautan->penyedia === PenyediaTautan::GoogleDrive && $tautan->drive_file_id !== null
            ? "https://drive.google.com/file/d/{$tautan->drive_file_id}/preview"
            : null;
    }

    public function urlGambar(TautanBerkas $tautan): ?string
    {
        return $tautan->penyedia === PenyediaTautan::GoogleDrive && $tautan->drive_file_id !== null
            ? "https://lh3.googleusercontent.com/d/{$tautan->drive_file_id}"
            : null;
    }
}

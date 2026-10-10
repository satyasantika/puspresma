<?php

namespace App\Actions\Bukti;

use App\Enums\JenisTautan;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Rules\TautanBerkasValid;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

/** Mengubah URL/label/izin publik tautan; perubahan URL tercatat lama → baru oleh observer + activitylog. */
class UbahTautanBukti
{
    public function jalankan(TautanBerkas $tautan, string $url, ?string $label, bool $tampilPublik, User $pelaku): TautanBerkas
    {
        Gate::forUser($pelaku)->authorize('update', $tautan);

        $url = trim($url);
        if ($url !== $tautan->url) {
            Validator::make(['url' => $url], ['url' => [new TautanBerkasValid]], attributes: ['url' => 'tautan'])->validate();
        }

        $tautan->update([
            'url' => $url,
            'label' => filled($label) ? $label : null,
            'tampil_publik' => $tampilPublik && $tautan->jenis === JenisTautan::FotoPenyerahan,
        ]);

        return $tautan;
    }
}

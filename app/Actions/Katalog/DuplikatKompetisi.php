<?php

namespace App\Actions\Katalog;

use App\Enums\StatusKompetisi;
use App\Models\Kompetisi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Menyalin kompetisi sebagai draf baru (lomba tahunan); tanggal digeser satu tahun, poster tidak disalin. */
class DuplikatKompetisi
{
    public function jalankan(Kompetisi $kompetisi, User $pelaku): Kompetisi
    {
        Gate::forUser($pelaku)->authorize('update', $kompetisi);

        return DB::transaction(function () use ($kompetisi, $pelaku): Kompetisi {
            $salinan = $kompetisi->replicate(['slug', 'status', 'diterbitkan_at', 'dibuat_oleh']);
            $salinan->forceFill([
                'judul' => $kompetisi->judul,
                'status' => StatusKompetisi::Draf,
                'dibuat_oleh' => $pelaku->getKey(),
                'tenggat_pendaftaran' => $kompetisi->tenggat_pendaftaran->copy()->addYear(),
                'tanggal_buka' => $kompetisi->tanggal_buka?->copy()->addYear(),
                'tanggal_pelaksanaan_mulai' => $kompetisi->tanggal_pelaksanaan_mulai?->copy()->addYear(),
                'tanggal_pelaksanaan_selesai' => $kompetisi->tanggal_pelaksanaan_selesai?->copy()->addYear(),
            ])->save();
            $salinan->prodiSasaran()->sync($kompetisi->prodiSasaran()->pluck('prodi.id')->all());

            return $salinan;
        });
    }
}

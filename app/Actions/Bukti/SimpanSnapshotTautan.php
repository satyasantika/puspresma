<?php

namespace App\Actions\Bukti;

use App\Models\Aktivitas;
use App\Models\Prestasi;
use App\Models\SnapshotTautanVerifikasi;
use App\Models\TautanBerkas;
use App\Models\User;

/** Menyalin setiap tautan bukti aktif ke snapshot yang tidak dapat diubah (BR-28). */
class SimpanSnapshotTautan
{
    /** @return int jumlah snapshot yang dibuat */
    public function jalankan(Prestasi $prestasi, User $pelaku, string $alasan = 'verifikasi', ?TautanBerkas $hanya = null): int
    {
        $tautan = $hanya === null ? $prestasi->tautanBukti()->get() : $prestasi->tautanBukti()->whereKey($hanya->getKey())->get();
        $dibuka = Aktivitas::query()
            ->where('log_name', 'tautan')->where('event', 'dibuka')
            ->where('causer_id', $pelaku->getKey())
            ->whereIn('subject_id', $tautan->pluck('id'))
            ->pluck('subject_id')->all();

        $tautan->each(fn (TautanBerkas $t) => SnapshotTautanVerifikasi::create([
            'prestasi_id' => $prestasi->id,
            'tautan_berkas_id' => $t->id,
            'jenis' => $t->jenis->value,
            'url' => $t->url,
            'drive_file_id' => $t->drive_file_id,
            'status_cek' => $t->status_cek->value,
            'alasan' => $alasan,
            'dibuka_verifikator' => in_array($t->id, $dibuka, true),
            'user_id' => $pelaku->id,
        ]));

        return $tautan->count();
    }
}

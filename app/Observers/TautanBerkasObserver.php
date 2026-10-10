<?php

namespace App\Observers;

use App\Enums\JenisTautan;
use App\Enums\StatusCekTautan;
use App\Jobs\PeriksaTautanBerkas;
use App\Models\TautanBerkas;
use App\Support\AnalisisTautan;
use Illuminate\Support\Facades\Cache;

class TautanBerkasObserver
{
    /** Menurunkan penyedia, drive_file_id, dan url_normal setiap kali URL berubah. */
    public function saving(TautanBerkas $tautan): void
    {
        if ($tautan->isDirty('url') || ! $tautan->exists) {
            $tautan->penyedia = AnalisisTautan::penyedia($tautan->url);
            $tautan->drive_file_id = AnalisisTautan::driveFileId($tautan->url);
            $tautan->url_normal = AnalisisTautan::normal($tautan->url);
        }
    }

    public function created(TautanBerkas $tautan): void
    {
        PeriksaTautanBerkas::dispatch($tautan)->afterCommit();
        $this->buangCache($tautan);
    }

    public function updated(TautanBerkas $tautan): void
    {
        if ($tautan->wasChanged('url')) {
            // Query langsung: instance di tangan pemanggil bisa usang (status dari pemeriksaan sebelumnya).
            $tautan->newQuery()->whereKey($tautan->getKey())->update(['status_cek' => StatusCekTautan::Belum->value, 'dicek_pada' => null]);
            $tautan->forceFill(['status_cek' => StatusCekTautan::Belum, 'dicek_pada' => null])->syncOriginalAttributes(['status_cek', 'dicek_pada']);
            PeriksaTautanBerkas::dispatch($tautan)->afterCommit();
        }

        $this->buangCache($tautan);
    }

    public function deleted(TautanBerkas $tautan): void
    {
        $this->buangCache($tautan);
    }

    public function restored(TautanBerkas $tautan): void
    {
        $this->buangCache($tautan);
    }

    /** Foto penyerahan memengaruhi galeri; poster memengaruhi katalog. */
    private function buangCache(TautanBerkas $tautan): void
    {
        match ($tautan->jenis) {
            JenisTautan::FotoPenyerahan => Cache::increment('galeri:versi'),
            JenisTautan::Poster => Cache::increment('katalog:versi'),
            default => null,
        };
    }
}

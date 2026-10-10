<?php

namespace App\Jobs;

use App\Models\Kompetisi;
use App\Models\Mahasiswa;
use App\Models\User;
use App\Notifications\KompetisiBaruTerbit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

/** Memberi tahu mahasiswa aktif prodi sasaran (atau semua) tentang kompetisi baru, per chunk 200 (database saja). */
class KirimKompetisiBaruTerbit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public Kompetisi $kompetisi)
    {
        $this->onQueue(config('puspresma.antrean.notifikasi'));
    }

    public function handle(): void
    {
        $prodi = $this->kompetisi->prodiSasaran()->pluck('prodi.id');

        Mahasiswa::query()->where('status_mahasiswa', 'aktif')->whereNotNull('user_id')
            ->when($prodi->isNotEmpty(), fn ($q) => $q->whereIn('prodi_id', $prodi))
            ->chunkById(200, function ($kelompok): void {
                $pengguna = User::whereIn('id', $kelompok->pluck('user_id'))->where('is_active', true)->get();
                Notification::send($pengguna, new KompetisiBaruTerbit($this->kompetisi));
            });
    }
}

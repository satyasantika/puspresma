<?php

namespace App\Filament\Shared\Concerns;

use App\Actions\Bukti\SinkronTautanBukti;
use App\Actions\Prestasi\SimpanAnggotaTim;
use App\Actions\Prestasi\SimpanPrestasi;
use App\Models\Prestasi;
use Illuminate\Support\Facades\DB;

/** Menyimpan data inti beserta tim (dan bukti, F4.4) dalam satu transaksi; UI hanya memanggil Action. */
trait SimpanRelasiPrestasi
{
    /** @param  array<string, mixed>  $data */
    protected function simpanSemua(?Prestasi $prestasi, array $data): Prestasi
    {
        $pelaku = auth()->user();

        return DB::transaction(function () use ($prestasi, $data, $pelaku): Prestasi {
            $prestasi = app(SimpanPrestasi::class)->jalankan($prestasi, $data, $pelaku);

            app(SimpanAnggotaTim::class)->jalankan(
                $prestasi,
                $data['anggota_internal'] ?? [],
                $data['anggota_eksternal'] ?? [],
                [
                    'dosen_pembimbing_id' => $data['dosen_pembimbing_id'] ?? null,
                    'nama_pembimbing_lain' => $data['nama_pembimbing_lain'] ?? null,
                ],
                $pelaku,
            );

            app(SinkronTautanBukti::class)->jalankan($prestasi, $data['tautan_bukti'] ?? [], $pelaku);

            return $prestasi;
        });
    }

    /**
     * Isi awal repeater tim saat membuka halaman ubah.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function muatRelasi(Prestasi $prestasi, array $data): array
    {
        $data['anggota_internal'] = $prestasi->anggota()->where('is_pengaju', false)->get()
            ->map(fn ($a) => ['mahasiswa_id' => $a->mahasiswa_id, 'peran' => $a->peran->value])->all();
        $data['anggota_eksternal'] = $prestasi->anggotaEksternal()->get()
            ->map(fn ($e) => $e->only(['nama', 'nomor_induk', 'institusi', 'program_studi']))->all();

        $data['tautan_bukti'] = $prestasi->tautanBukti()->get()->map(fn ($t) => [
            'id' => $t->id,
            'jenis' => $t->jenis->value,
            'url' => $t->url,
            'label' => $t->label,
            'tampil_publik' => $t->tampil_publik,
            'status_cek' => $t->status_cek->label(),
        ])->all();

        return $data;
    }
}

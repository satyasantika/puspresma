<?php

namespace App\Services\Ekspor;

use App\Models\Prestasi;
use App\Models\User;
use Generator;

/** LAP-01: satu baris per prestasi. URL sertifikat diambil dari snapshot verifikasi dan hanya untuk admin. */
class RekapPrestasiLaporan extends LaporanEkspor
{
    public function jenis(): string
    {
        return 'rekap_prestasi';
    }

    public function judul(): string
    {
        return 'Rekap Prestasi Mahasiswa';
    }

    public function header(User $pengguna): array
    {
        return [
            'No. Registrasi', 'Kegiatan', 'Penyelenggara', 'Jenis', 'Bidang', 'Kategori', 'Tingkat', 'Capaian', 'Bentuk', 'Tanggal Mulai',
            'Tanggal Selesai', 'Moda', 'Lokasi', 'Jumlah Peserta', 'Jumlah PT', 'Jumlah Negara', 'Pembimbing', 'Anggota', 'Status',
            ...($this->denganUrl($pengguna) ? ['URL Sertifikat (snapshot)'] : []),
        ];
    }

    public function jumlah(array $filter, User $pengguna): int
    {
        return QueryPrestasiLaporan::bangun($filter, $pengguna)->count();
    }

    public function baris(array $filter, User $pengguna): Generator
    {
        $denganUrl = $this->denganUrl($pengguna);
        $with = ['jenisKegiatan', 'kategoriLomba', 'tingkat', 'capaian', 'dosenPembimbing', 'anggota.mahasiswa', 'anggota.prodi'];
        if ($denganUrl) {
            $with[] = 'snapshotTautan';
        }

        foreach (QueryPrestasiLaporan::bangun($filter, $pengguna)->with($with)->lazyById(500, 'prestasi.id', 'id') as $p) {
            /** @var Prestasi $p */
            yield [
                $p->nomor_registrasi, $p->nama_kegiatan, $p->penyelenggara, $p->jenisKegiatan->nama, $p->bidang->label(), $p->kategoriLomba?->nama,
                $p->tingkat->nama, $p->capaian->nama, $p->bentuk->label(), $p->tanggal_mulai->toDateString(), $p->tanggal_selesai->toDateString(),
                $p->moda->label(), $p->lokasi, $p->jumlah_peserta, $p->jumlah_pt_peserta, $p->jumlah_negara_peserta,
                $p->dosenPembimbing !== null ? $p->dosenPembimbing->nama_lengkap : $p->nama_pembimbing_lain,
                $p->anggota->map(fn ($a) => "{$a->mahasiswa->npm}-{$a->mahasiswa->nama}-{$a->prodi->nama}")->implode('; '),
                $p->status->label(),
                ...($denganUrl ? [$this->urlSertifikat($p)] : []),
            ];
        }
    }

    private function denganUrl(User $pengguna): bool
    {
        return $pengguna->canAny(['prestasi.verifikasi', 'prestasi.verifikasi-prodi']);
    }

    /** URL sertifikat dari snapshot TERBARU per tautan (bukan tautan yang mungkin berubah setelah verifikasi). */
    private function urlSertifikat(Prestasi $p): string
    {
        return $p->snapshotTautan->where('jenis', 'sertifikat_piagam')->groupBy('tautan_berkas_id')
            ->map(fn ($g) => $g->sortByDesc('created_at')->first()->url)->implode(' ; ');
    }
}

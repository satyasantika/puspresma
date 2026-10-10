<?php

namespace App\Services;

use App\Enums\StatusKonfirmasiPembimbing;
use App\Models\Prestasi;

/** Peringatan validasi bagi verifikator (tidak memblokir kecuali duplikat/pembimbing disangkal; BR-05, BR-10, BR-11, BR-30). */
class PeringatanPrestasi
{
    /** @return list<string> */
    public static function untuk(Prestasi $prestasi): array
    {
        $prestasi->loadMissing('tingkat');
        $hasil = [];

        $adaDuplikat = $prestasi->getAttributes()['ada_duplikat'] ?? null;
        if ($adaDuplikat === null ? $prestasi->adaKandidatDuplikatTerbuka() : (bool) $adaDuplikat) {
            $hasil[] = 'Ada kandidat duplikat yang belum ditinjau.';
        }

        $bermasalah = $prestasi->getAttributes()['tautan_bermasalah'] ?? null;
        if ($bermasalah === null
            ? $prestasi->tautanBukti()->where('status_cek', 'tidak_dapat_diakses')->exists()
            : (bool) $bermasalah) {
            $hasil[] = 'Ada tautan bukti yang tidak dapat diakses (mungkin dibagikan terbatas).';
        }

        if ($prestasi->status_konfirmasi_pembimbing === StatusKonfirmasiPembimbing::Menunggu) {
            $hasil[] = 'Konfirmasi dosen pembimbing masih menunggu.';
        }
        if ($prestasi->status_konfirmasi_pembimbing === StatusKonfirmasiPembimbing::Disangkal) {
            $hasil[] = 'Dosen pembimbing menyangkal pembimbingan.';
        }

        $kode = $prestasi->tingkat?->kode;
        $ambangNegara = (int) Pengaturan::ambil('ambang_negara_internasional', 3);
        if ($kode === 'internasional' && ($prestasi->jumlah_negara_peserta ?? 0) < $ambangNegara) {
            $hasil[] = "Jumlah negara peserta ({$prestasi->jumlah_negara_peserta}) di bawah ambang {$ambangNegara} (perlu verifikasi).";
        }

        $ambangPt = Pengaturan::ambil('ambang_pt_nasional');
        if ($kode === 'nasional' && $ambangPt !== null && ($prestasi->jumlah_pt_peserta ?? 0) < (int) $ambangPt) {
            $hasil[] = "Jumlah perguruan tinggi peserta ({$prestasi->jumlah_pt_peserta}) di bawah ambang {$ambangPt} (perlu verifikasi).";
        }

        return $hasil;
    }
}

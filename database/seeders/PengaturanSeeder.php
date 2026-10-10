<?php

namespace Database\Seeders;

use App\Models\Pengaturan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/** Nilai bawaan pengaturan; idempoten dan tidak menimpa nilai yang sudah diubah admin. */
class PengaturanSeeder extends Seeder
{
    public const TEKS_PERSETUJUAN = 'Saya menyetujui nama, program studi, judul kegiatan, capaian, dan foto penyerahan prestasi saya ditampilkan di galeri publik FKIP Universitas Siliwangi. Persetujuan ini dapat saya tarik kapan saja. (Draf, perlu ditinjau bagian hukum.)';

    /** @return list<array{kunci: string, nilai: ?string, tipe: string, grup: string, keterangan: string}> */
    public static function daftar(): array
    {
        return [
            ['kunci' => 'verifikasi_prodi_aktif', 'nilai' => '0', 'tipe' => 'bool', 'grup' => 'verifikasi', 'keterangan' => 'Aktifkan tahap verifikasi oleh admin prodi sebelum fakultas (BR-09).'],
            ['kunci' => 'poin_aktif', 'nilai' => '0', 'tipe' => 'bool', 'grup' => 'poin', 'keterangan' => 'Aktifkan perhitungan poin prestasi (opsional, BR-18).'],
            ['kunci' => 'mode_poin_tim', 'nilai' => 'penuh', 'tipe' => 'string', 'grup' => 'poin', 'keterangan' => 'penuh = tiap anggota menerima poin penuh; dibagi = poin dibagi rata.'],
            ['kunci' => 'galeri_aktif', 'nilai' => '1', 'tipe' => 'bool', 'grup' => 'galeri', 'keterangan' => 'Tampilkan galeri prestasi publik.'],
            ['kunci' => 'ambang_negara_internasional', 'nilai' => '3', 'tipe' => 'int', 'grup' => 'validasi', 'keterangan' => 'Jumlah negara minimal untuk tingkat internasional (perlu verifikasi; hanya peringatan).'],
            ['kunci' => 'ambang_pt_nasional', 'nilai' => null, 'tipe' => 'int', 'grup' => 'validasi', 'keterangan' => 'Jumlah perguruan tinggi minimal untuk tingkat nasional (kosong = tanpa ambang; perlu verifikasi).'],
            ['kunci' => 'ambang_kemiripan_duplikat', 'nilai' => '0.80', 'tipe' => 'decimal', 'grup' => 'duplikat', 'keterangan' => 'Ambang kemiripan nama kegiatan untuk kandidat duplikat (0,50–1,00).'],
            ['kunci' => 'rentang_hari_duplikat', 'nilai' => '7', 'tipe' => 'int', 'grup' => 'duplikat', 'keterangan' => 'Selisih tanggal maksimal (hari) untuk kandidat duplikat (0–60).'],
            ['kunci' => 'batas_klaim_bulan', 'nilai' => '24', 'tipe' => 'int', 'grup' => 'validasi', 'keterangan' => 'Batas usia kegiatan (bulan) yang masih boleh diklaim.'],
            ['kunci' => 'awal_tahun_akademik_bulan', 'nilai' => '8', 'tipe' => 'int', 'grup' => 'validasi', 'keterangan' => 'Bulan awal tahun akademik 1–12 (perlu verifikasi).'],
            ['kunci' => 'iku_tingkat_minimal', 'nilai' => 'nasional', 'tipe' => 'string', 'grup' => 'laporan', 'keterangan' => 'Tingkat minimal pemenuh IKU mahasiswa berprestasi (lokal/wilayah/nasional/internasional); definisi IKU perlu verifikasi.'],
            ['kunci' => 'iku_hanya_juara', 'nilai' => '1', 'tipe' => 'bool', 'grup' => 'laporan', 'keterangan' => 'Hanya capaian juara yang dihitung pada IKU; definisi IKU perlu verifikasi.'],
            ['kunci' => 'wa_aktif', 'nilai' => '0', 'tipe' => 'bool', 'grup' => 'whatsapp', 'keterangan' => 'Aktifkan pengiriman notifikasi WhatsApp (Fonnte).'],
            ['kunci' => 'teks_persetujuan_publikasi', 'nilai' => self::TEKS_PERSETUJUAN, 'tipe' => 'string', 'grup' => 'galeri', 'keterangan' => 'Teks persetujuan publikasi (ditinjau bagian hukum).'],
        ];
    }

    public function run(): void
    {
        foreach (self::daftar() as $baris) {
            // Hanya membuat bila belum ada: nilai yang sudah diubah admin dipertahankan.
            Pengaturan::firstOrCreate(['kunci' => $baris['kunci']], $baris);
        }

        Cache::forget('pengaturan:semua');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Kompetisi;
use App\Services\GaleriPublik;
use App\Services\KatalogPublik;
use Illuminate\Contracts\View\View;

class BerandaController extends Controller
{
    /** Panduan bergambar per peran. Panduan super-admin sengaja tidak ditampilkan di halaman publik. */
    private const PANDUAN = [
        ['nama' => 'Mahasiswa', 'berkas' => 'mahasiswa.html', 'ringkas' => 'Mengajukan prestasi, menambahkan tim dan bukti, memantau status, dan mengatur persetujuan galeri.'],
        ['nama' => 'Dosen pembimbing', 'berkas' => 'dosen.html', 'ringkas' => 'Mengonfirmasi bimbingan prestasi mahasiswa dan memantau prestasi yang dibimbing.'],
        ['nama' => 'Admin prodi', 'berkas' => 'admin-prodi.html', 'ringkas' => 'Memverifikasi prestasi tahap prodi, meninjau duplikat, dan melihat laporan prodi.'],
        ['nama' => 'Admin fakultas', 'berkas' => 'admin-fakultas.html', 'ringkas' => 'Verifikasi akhir, kelola master, kompetisi, galeri, surat keterangan, dan ekspor.'],
        ['nama' => 'Pimpinan', 'berkas' => 'pimpinan.html', 'ringkas' => 'Membaca dasbor dan laporan kinerja prestasi fakultas untuk pengambilan keputusan.'],
    ];

    /** Beranda publik: statistik publik, kompetisi dengan tenggat terdekat, prestasi terbaru, dan panduan per peran. */
    public function __invoke(KatalogPublik $katalog, GaleriPublik $galeri): View
    {
        return view('welcome', [
            'kompetisi' => $katalog->terdekat(4),
            'prestasi' => $galeri->terbaru(6),
            // Hanya angka yang sudah publik: prestasi di galeri (berizin; cache galeri dibuang seketika saat izin ditarik)
            // dan kompetisi terbit yang tenggatnya belum lewat.
            'statistik' => [
                'prestasi' => $galeri->daftar()['total'],
                'kompetisi' => Kompetisi::query()->masihDibuka()->count(),
            ],
            'panduan' => self::PANDUAN,
        ]);
    }
}

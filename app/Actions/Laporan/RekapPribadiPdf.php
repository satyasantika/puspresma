<?php

namespace App\Actions\Laporan;

use App\Models\PrestasiAnggota;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** LAP-07: rekap prestasi terverifikasi milik mahasiswa sendiri (maks 200 baris), di-stream tanpa menyimpan berkas. */
class RekapPribadiPdf
{
    public const BATAS = 200;

    public function stream(User $pengguna): StreamedResponse
    {
        $pengguna->loadMissing('mahasiswa');
        if ($pengguna->mahasiswa === null) {
            throw new AuthorizationException('Hanya mahasiswa yang dapat mengunduh rekap prestasi pribadi.');
        }

        $baris = PrestasiAnggota::query()->where('mahasiswa_id', $pengguna->mahasiswa->id)
            ->whereHas('prestasi', fn ($q) => $q->terverifikasi())
            ->with(['prestasi.tingkat', 'prestasi.capaian', 'prestasi.jenisKegiatan'])->get()
            ->sortByDesc(fn ($a) => $a->prestasi->tanggal_selesai)->take(self::BATAS)
            ->map(fn (PrestasiAnggota $a): array => [
                $a->prestasi->nomor_registrasi, $a->prestasi->nama_kegiatan, $a->prestasi->penyelenggara, $a->prestasi->tingkat->nama,
                $a->prestasi->capaian->nama, $a->prestasi->tanggal_selesai->translatedFormat('d F Y'),
            ])->values()->all();

        $pdf = Pdf::loadView('pdf.tabel-laporan', [
            'judul' => "Rekap Prestasi — {$pengguna->mahasiswa->nama} ({$pengguna->mahasiswa->npm})",
            'header' => ['No. Registrasi', 'Kegiatan', 'Penyelenggara', 'Tingkat', 'Capaian', 'Tanggal Selesai'],
            'baris' => $baris,
            'filterTeks' => 'Prestasi terverifikasi milik sendiri',
            'dicetakOleh' => $pengguna->name,
            'tanggalCetak' => now()->translatedFormat('d F Y H:i'),
            'catatanKaki' => 'Dokumen ini bahan SKPI/portofolio, bukan dokumen resmi. Untuk surat resmi, mintalah Surat Keterangan Prestasi.',
        ])->setPaper('a4', 'landscape')->output();

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf;
        }, 'rekap-prestasi-'.now()->format('Ymd').'.pdf', ['Content-Type' => 'application/pdf']);
    }
}

<?php

namespace App\Services;

use App\Enums\StatusSurat;
use App\Models\SuratKeteranganPrestasi;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Mencetak surat ke PDF on-the-fly (dompdf + QR data URI) lalu di-stream; tidak ada berkas yang ditulis (BR-24). */
class CetakSuratKeteranganPrestasi
{
    public function stream(SuratKeteranganPrestasi $surat): StreamedResponse
    {
        $isi = $this->pdf($surat);

        return response()->streamDownload(function () use ($isi): void {
            echo $isi;
        }, 'surat-keterangan-prestasi-'.str_replace(['/', '\\'], '-', $surat->nomor).'.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function pdf(SuratKeteranganPrestasi $surat): string
    {
        $surat->load(['mahasiswa.prodi', 'prestasi.tingkat', 'prestasi.capaian']);

        return Pdf::loadView('pdf.surat-keterangan-prestasi', [
            'surat' => $surat,
            'qr' => $this->qr($surat),
            'dicabut' => $surat->status === StatusSurat::Dicabut,
        ])->setPaper('a4', 'portrait')->output();
    }

    /** QR berisi URL verifikasi publik; PNG sebagai data URI. */
    public function qr(SuratKeteranganPrestasi $surat): string
    {
        return (new Builder(data: route('verifikasi.surat', $surat->getKey()), size: 160, margin: 4))->build()->getDataUri();
    }
}

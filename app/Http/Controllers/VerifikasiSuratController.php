<?php

namespace App\Http\Controllers;

use App\Models\SuratKeteranganPrestasi;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class VerifikasiSuratController extends Controller
{
    /** Halaman publik: nomor, nama, prodi, tanggal terbit, status, dan judul prestasi — tanpa NPM, surel, atau tautan bukti. */
    public function __invoke(string $id): View
    {
        $data = Cache::remember("verifikasi-surat:{$id}", now()->addHour(), function () use ($id): ?array {
            if (! preg_match('/^[0-9a-f-]{36}$/i', $id)) {
                return null;
            }

            $surat = SuratKeteranganPrestasi::with(['mahasiswa.prodi', 'prestasi.tingkat', 'prestasi.capaian'])->find($id);

            return $surat === null ? null : [
                'nomor' => $surat->nomor,
                'nama' => $surat->mahasiswa->nama,
                'prodi' => $surat->mahasiswa->prodi?->nama,
                'tanggal_terbit' => $surat->tanggal_terbit->translatedFormat('d F Y'),
                'status' => $surat->status->label(),
                'dicabut' => $surat->status->value === 'dicabut',
                'tanggal_dicabut' => $surat->dicabut_at?->translatedFormat('d F Y'),
                'prestasi' => $surat->prestasi->map(fn ($p) => ['judul' => $p->nama_kegiatan, 'tingkat' => $p->tingkat->nama, 'capaian' => $p->capaian->nama])->all(),
            ];
        });

        abort_if($data === null, 404);

        return view('surat.verifikasi', ['surat' => $data]);
    }
}

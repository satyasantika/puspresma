<?php

namespace App\Services\Ekspor;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\SimpleExcel\SimpleExcelWriter;

/** Menulis laporan ke XLSX/CSV (streaming) atau PDF A4 landscape; sel dilindungi dari injeksi rumus. */
class PenulisBerkas
{
    /**
     * @param  list<string>  $header
     * @param  iterable<int, list<string|int|float|null>>  $baris
     * @return int jumlah baris data yang ditulis
     */
    public static function tabel(string $target, string $tipe, array $header, iterable $baris): int
    {
        $penulis = SimpleExcelWriter::create($target, $tipe);
        $jumlah = 0;

        foreach ($baris as $isi) {
            $penulis->addRow(array_combine($header, array_map([self::class, 'aman'], array_pad($isi, count($header), null))));
            $jumlah++;
        }

        if ($jumlah === 0) {
            $penulis->addHeader($header);
        }

        $penulis->close();

        return $jumlah;
    }

    /**
     * Menulis semua lembar laporan: XLSX multi-sheet, atau CSV (lembar pertama saja).
     *
     * @param  list<array{nama: string, header: list<string>, baris: iterable<int, list<string|int|float|null>>}>  $lembar
     * @return int jumlah baris data pada lembar terakhir (lampiran) atau satu-satunya lembar
     */
    public static function buku(string $target, string $tipe, array $lembar): int
    {
        if (count($lembar) === 1 || $tipe === 'csv') {
            return self::tabel($target, $tipe, $lembar[0]['header'], $lembar[0]['baris']);
        }

        $penulis = SimpleExcelWriter::create($target, $tipe);
        $jumlah = 0;

        foreach ($lembar as $indeks => $satu) {
            $indeks === 0 ? $penulis->nameCurrentSheet($satu['nama']) : $penulis->addNewSheetAndMakeItCurrent()->nameCurrentSheet($satu['nama']);
            $penulis->addHeader($satu['header']);
            $jumlah = 0;
            foreach ($satu['baris'] as $isi) {
                $penulis->addRow(array_combine($satu['header'], array_map([self::class, 'aman'], array_pad($isi, count($satu['header']), null))));
                $jumlah++;
            }
        }

        $penulis->close();

        return $jumlah;
    }

    /**
     * @param  list<array{nama: string, header: list<string>, baris: iterable<int, list<string|int|float|null>>}>  $lembar
     * @param  array<string, mixed>  $filter
     */
    public static function pdf(LaporanEkspor $laporan, User $pengguna, array $filter, array $lembar): string
    {
        return Pdf::loadView('pdf.tabel-laporan', [
            'judul' => $laporan->judul(),
            'lembar' => array_map(fn (array $l) => [
                'nama' => $l['nama'],
                'header' => $l['header'],
                'baris' => is_array($l['baris']) ? $l['baris'] : iterator_to_array($l['baris'], false),
            ], $lembar),
            'filterTeks' => $laporan->keteranganFilter($filter),
            'dicetakOleh' => $pengguna->name,
            'tanggalCetak' => now()->translatedFormat('d F Y H:i'),
            'catatanKaki' => $laporan->catatanKaki(),
        ])->setPaper('a4', 'landscape')->output();
    }

    public static function aman(mixed $nilai): mixed
    {
        return is_string($nilai) && preg_match('/^[=+\-@\t\r]/', $nilai) === 1 ? "'".$nilai : $nilai;
    }
}

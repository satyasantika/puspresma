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
     * @param  list<string>  $header
     * @param  iterable<int, list<string|int|float|null>>  $baris
     * @param  array<string, mixed>  $filter
     */
    public static function pdf(LaporanEkspor $laporan, User $pengguna, array $filter, array $header, iterable $baris): string
    {
        return Pdf::loadView('pdf.tabel-laporan', [
            'judul' => $laporan->judul(),
            'header' => $header,
            'baris' => is_array($baris) ? $baris : iterator_to_array($baris, false),
            'filterTeks' => $laporan->keteranganFilter($filter),
            'dicetakOleh' => $pengguna->name,
            'tanggalCetak' => now()->translatedFormat('d F Y H:i'),
        ])->setPaper('a4', 'landscape')->output();
    }

    public static function aman(mixed $nilai): mixed
    {
        return is_string($nilai) && preg_match('/^[=+\-@\t\r]/', $nilai) === 1 ? "'".$nilai : $nilai;
    }
}

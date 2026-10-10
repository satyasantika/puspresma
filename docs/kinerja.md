# Hasil Uji Kinerja Laporan dan Dasbor

Diukur pada F8.6 dengan `DemoSeeder` (`DEMO_PRESTASI=10000`: 3 prodi, 200 mahasiswa, 20 dosen, 10.000 prestasi — 60% terverifikasi — dengan 1–4 anggota per prestasi, tautan sertifikat, dan snapshot verifikasi).

- **Lingkungan**: Docker lokal (`puspresma-php`, PHP 8.5), MariaDB 10.11 native di host (`db_puspresma_testing`), Redis bersama (cache). Bukan server produksi; angka produksi akan berbeda.
- **Cara**: `DB_DATABASE=db_puspresma_testing DEMO_PRESTASI=10000 php artisan db:seed --class=DemoSeeder` (≈ 6 detik), lalu skrip yang memanggil layanan `StatistikPrestasi`, `GaleriPublik`, dan `PenulisBerkas` (XLSX streaming, chunk 500) dengan pengguna admin-fakultas.
- **Data terverifikasi**: 6.028 prestasi, 17.976 baris anggota.

| Pengukuran | Waktu | Memori puncak | Keterangan |
|---|---:|---:|---|
| Dasbor — cache dingin (7 agregat) | 174 ms | 46,5 MB | satu kueri `GROUP BY` per metode |
| Dasbor — cache hangat | 2 ms | 46,5 MB | `Cache::flexible`, tanpa kueri |
| Galeri `/galeri` hal. 1 — cache dingin | 69 ms | 46,5 MB | 12 kartu |
| Galeri `/galeri` hal. 1 — cache hangat | 1 ms | 46,5 MB | |
| LAP-01 Rekap prestasi (XLSX) | 2,95 dtk | 68,5 MB | 6.028 baris, 0,92 MB |
| LAP-02 Rekap per anggota (XLSX) | 3,94 dtk | 68,5 MB | 17.976 baris, 1,29 MB |
| Ekspor SIMKATMAWA (XLSX) | 5,92 dtk | 72,5 MB | 17.976 baris (satu per anggota), 1,68 MB |
| LAP-03 Akreditasi satu prodi (XLSX) | 0,84 dtk | 72,5 MB | 4.138 baris lampiran |
| LAP-04 IKU (XLSX) | 0,14 dtk | 72,5 MB | 1.055 baris |

## Catatan

- Seluruh ekspor di atas melebihi 2.000 baris sehingga di produksi berjalan di antrean `ekspor` (Horizon) dan hasilnya tersimpan di `storage/app/tmp` selama ≤ 24 jam; memori puncak tetap rendah karena penulisan streaming per 500 baris.
- Kriteria PRD (dasbor dan galeri < 1 detik dari cache; daftar admin < 500 ms pada 50.000 prestasi) terpenuhi untuk dasbor dan galeri; daftar admin 50.000 prestasi belum diukur.
- Pengukuran ini menemukan dan memperbaiki cacat: iterasi `lazyById` yang mempertahankan urutan tanggal membuat baris terlewat pada ekspor di atas 500 baris (kini memakai `QueryPrestasiLaporan::iterasi`, diuji dengan ukuran chunk kecil).

## Jumlah kueri per halaman (F9.2)

Dijaga oleh `tests/Feature/Kinerja/JumlahKueriTest.php` (25 baris data, cache dihangatkan; lazy loading dilarang di test):

| Halaman | Sebelum | Sesudah | Batas |
|---|---|---|---|
| Antrean verifikasi admin | 37 | 15 | ≤ 15 |
| Prestasi Saya | 40 | ≤ 15 | ≤ 15 |
| Galeri, katalog, dasbor | lulus | lulus | ≤ 15 |

Penyebab dan perbaikan:

- `PeringatanPrestasi` memicu `exists` per baris karena nilai subquery `NULL` dianggap "atribut tidak dimuat"; kini dibedakan dengan `array_key_exists`.
- `PrestasiPolicy` (milik/prodi) mengkueri `anggota()` per baris; kini memakai relasi `anggota` yang dimuat eager di tabel.
- Opsi filter Prodi/Tingkat/Capaian di-cache 5 menit (`opsi:filter-*`).

EXPLAIN: subquery penanda memakai indeks yang sudah ada (`kandidat_duplikat.prestasi_id` lewat FK/unik komposit, dan `tautan_berkas_pemilik_jenis_index`), sehingga tidak diperlukan migrasi indeks baru.

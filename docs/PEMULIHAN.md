# Pemulihan Basis Data — Puspresma FKIP Unsil

Yang dicadangkan **hanya basis data** (`spatie/laravel-backup`, `backup:run --only-db`). Bukti prestasi berupa tautan Google Drive milik mahasiswa;
tidak ada berkas pengguna di server. Arsip `.zip` terenkripsi (`BACKUP_ARCHIVE_PASSWORD`) disimpan di disk `backup` (volume terpisah, atau S3/MinIO via `BACKUP_DISK=s3`).

## Jadwal dan retensi

| Perintah | Waktu | Keterangan |
|---|---|---|
| `backup:run --only-db` | harian 01:00 | arsip terenkripsi |
| `backup:clean` | harian 01:30 | semua 7 hari, harian 30 hari, mingguan 8 minggu, bulanan 6 bulan |
| `backup:monitor` | harian 09:00 | cadangan terakhir ≤ 1 hari, total ≤ 5 GB |

Kegagalan backup/pembersihan/monitor dikirim ke surel seluruh pengguna berperan `super-admin`.
Password arsip **harus disimpan terpisah dari server** (pengelola kata sandi fakultas); tanpa password arsip tidak dapat dibuka.

## Langkah pulih ke server kosong

1. Siapkan server sesuai `docs/DEPLOY.md` (clone, `.env`, `build`), tetapi **jangan** jalankan migrasi dulu. Buat basis data kosong `utf8mb4`.
2. Ambil arsip terbaru dari disk `backup` (atau S3) ke direktori kerja yang aman.
3. Ekstrak dengan password (berkas berada di `db-dumps/`):
   ```bash
   7z x -p"$BACKUP_ARCHIVE_PASSWORD" arsip.zip -odump   # atau: unzip -P "$BACKUP_ARCHIVE_PASSWORD" arsip.zip -d dump
   ```
4. Impor: `mariadb -h <host> -u <user> -p db_puspresma < dump/db-dumps/mariadb-db_puspresma.sql`
5. `docker compose -f docker-compose.prod.yml exec app php artisan migrate --force` (menyusul migrasi yang lebih baru dari arsip).
6. `php artisan optimize` lalu naikkan `queue` dan `scheduler`.
7. Verifikasi: `GET /api/health` → `status: ok`; masuk sebagai admin-fakultas; buka sampel prestasi terverifikasi: tautan bukti tampil dan
   **snapshot tautan** (`snapshot_tautan_verifikasi`) terbaca; cek galeri publik dan `/verifikasi/{id}` pada satu surat.
8. Catat hasil pada formulir di bawah.

## Anjuran kepada fakultas

Bukti prestasi tingkat **nasional/internasional** sebaiknya juga disalin ke Shared Drive fakultas. Itu tanggung jawab unit, bukan sistem:
sistem hanya menyimpan tautan dan snapshot URL, bukan isi berkas.

## Uji pulih tiap semester

Jalankan prosedur di atas ke basis data uji **setiap awal semester** (Februari dan Agustus) dan catat hasilnya.

### Formulir catatan uji pulih

| Butir | Isian |
|---|---|
| Tanggal uji / penguji | |
| Arsip yang dipakai (nama, tanggal) | |
| Dekripsi berhasil | ya / tidak |
| Impor tanpa galat | ya / tidak |
| `/api/health` ok | ya / tidak |
| Sampel tautan bukti dan snapshot terbaca | ya / tidak |
| Lama pemulihan (menit) | |
| Catatan / tindak lanjut | |

## Hasil uji 2026-10-10 (pengembangan)

`backup:run --only-db` dari citra produksi menghasilkan arsip terenkripsi (AES); dekripsi dengan password benar, impor ke basis data kosong, dan
seluruh skema terpulihkan tanpa galat. Basis data pengembangan tidak berisi baris data, sehingga sampel tautan/snapshot belum teruji pada data nyata:
ulangi pada langkah go-live dengan cadangan data produksi.

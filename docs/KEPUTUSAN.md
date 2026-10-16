# Catatan Keputusan Desain

## K-01 — MariaDB sebagai pengganti MySQL 8.4

> Diselaraskan oleh keputusan 16 Oktober 2026: standar kini PHP 8.5 + MariaDB.

Basis data memakai MariaDB 10.11 native di host (`db_puspresma`, `db_puspresma_testing`; koneksi `DB_CONNECTION=mariadb`, `DB_HOST=host.docker.internal`). Kolom `uuid()` Laravel dipetakan ke tipe native `uuid` MariaDB. Collation `utf8mb4_unicode_ci` (bawaan Laravel untuk MariaDB) menggantikan `utf8mb4_0900_ai_ci` yang hanya ada di MySQL 8. Kredensial basis data dibaca dari `.env` lokal dan tidak pernah di-commit.

## K-02 — Compose pusat, bukan `docker-compose.yml` di repo

Lingkungan lokal memakai compose pusat `~/code/docker-compose.yml` (service `puspresma-php` PHP 8.5 dan `puspresma-nginx` di port host 8032, jaringan `laranet`; konfigurasi nginx `~/code/docker/nginx/puspresma.conf`). Repo ini tidak memuat `docker-compose.yml` dev maupun `docker/php/Dockerfile` dev; yang ada hanya `Dockerfile` dan `docker-compose.prod.yml` untuk produksi. Perintah: `docker exec -w /var/www/html puspresma-php <cmd>` (alias `psp`). Node tidak ada di container sehingga `npm` dijalankan di host. Setelah container `puspresma-php` dibuat ulang, `puspresma-nginx` perlu `nginx -s reload` agar memakai IP upstream baru (gejala: 502).

## K-03 — Versi dan port

> Diselaraskan oleh keputusan 16 Oktober 2026: standar kini PHP 8.5 + MariaDB. (Port 8032 vs 8016 tetap menunggu keputusan pemilik, lihat K-19.)

PHP 8.5 (`composer.json` `"php": "^8.5"`, CI dan Dockerfile produksi 8.5). Port lokal 8032 (bukan 8016) dan loopback produksi 8032. Gateway lokal `:8080` tidak tersedia; subpath diuji lewat konfigurasi override di test.

## K-04 — Berkas yang tidak di-commit

`.env` dan variannya, dump basis data, isi `storage/app/tmp`, token layanan, serta bahan kerja lokal di luar aplikasi (PDF regulasi, catatan pengembangan) berada di `.gitignore` dan tidak menjadi bagian repositori.

## K-05 — Redis

> **Digantikan oleh Profil Ringan (STANDAR-TEKNIS §1b, keputusan 15 Oktober 2026), diterapkan di kode (K-21).** Cache, sesi, antrean, dan lock memakai driver database; Redis tidak lagi dipakai. Berikut keputusan awal (riwayat).

Redis memakai container bersama `redis` (DB 40 default, 41 cache, 42 antrean; prefiks `puspresma_`). Kata sandi dibaca dari `.env` lokal. Test memakai driver array/sync dan tidak menyentuh Redis.

## K-06 — Service Horizon dan scheduler di compose pusat

> **Digantikan oleh Profil Ringan, diterapkan di kode (K-21).** Service `puspresma-horizon` di compose pusat harus dihapus (di luar repo); `puspresma-scheduler` (`schedule:work`) tetap dan sekaligus menyalakan worker antrean (`--stop-when-empty` tiap menit, antrean `default` dan `berat`). Berikut keputusan awal (riwayat).

Ditambahkan ke `~/code/docker-compose.yml` (di luar repo): `puspresma-horizon` (`php artisan horizon`, `stop_grace_period: 60s`) dan `puspresma-scheduler` (`schedule:work`), memakai image PHP 8.5 dan kode yang sama. Test tidak menyentuh Redis: `phpunit.xml` memaksa cache/sesi `array` dan antrean `sync`, sehingga pengaturan Redis diuji lewat `.env.example` dan konfigurasi koneksi (`RedisHorizonTest`).

## K-07 — CI, deploy, dan berkas produksi

`ci.yml` memakai MariaDB 10.11 dan PHP 8.5. Job `deploy` ada di berkas yang sama, hanya berjalan setelah uji hijau, lalu menjalankan `scripts/deploy.sh` di server lewat SSH (lihat `docs/DEPLOY.md`). Repositori GitHub dan secret dibuat oleh pemilik. Gotenberg tidak dipakai; `filament:upgrade` dijalankan saat build image, dan daftar tabel `uji-pulih.sh` mengikuti tabel domain.

## K-08 — Pemisahan tugas super-admin pada verifikasi

Super-admin menerima seluruh permission di seeder, tetapi `Gate::before` menolak tiga ability Policy `verifikasi`, `loloskanProdi`, dan `bukaVerifikasi` untuknya. Policy baru wajib memakai nama ability tersebut (bukan `can('prestasi.verifikasi')` langsung) agar pengecualian berlaku, karena pemeriksaan permission spatie berjalan lebih dahulu daripada `Gate::before` aplikasi. Gerbang Horizon (hilang setelah Fase B) memakai peran `super-admin`, bukan permission `horizon.akses`; permission itu tetap dibuat untuk kelengkapan.

## K-09 — Pengalihan panel sebelum Authenticate

`ArahkanKePanelSesuaiPeran` didaftarkan lewat `prependToPriorityList` agar berjalan sebelum `Authenticate` Filament (yang menjawab 403 bagi pengguna tanpa akses panel). Pengalihan hanya untuk permintaan GET/HEAD. Tabel `notifications` diperlukan oleh `databaseNotifications()` panel.

## K-10 — Retensi log aktivitas dan batas login

Retensi log aktivitas `clean_after_days` = 730 hari (di activitylog v5 kuncinya `clean_after_days`, bukan `delete_records_older_than_days`); angka ini asumsi yang perlu ditetapkan fakultas. Limiter `login` (5/menit per surel+IP) diterapkan pada halaman masuk bersama kedua panel (`App\Filament\Pages\Auth\Masuk`) dan dibersihkan saat login berhasil; log `auth` hanya menyimpan surel, IP, dan user agent. Model `User` hanya mencatat `name`, `email`, `is_active`, dan peristiwa peran (`peran_ditambah`/`peran_dicabut`) lewat event spatie permission (`events_enabled` = true).

## K-11 — morphMap tanpa `enforceMorphMap`

Mode `Relation::enforceMorphMap` tidak dipakai karena juga berlaku bagi subjek/pelaku activitylog (`subject_type`, `causer_type`), sehingga setiap model berjejak audit (Prodi, User, dst.) akan melempar `ClassMorphViolationException` kecuali seluruhnya didaftarkan. Karena itu dipakai `Relation::morphMap` biasa: alias `prestasi` dan `kompetisi` terdaftar untuk `tautan_berkas.pemilik_type`, model lain tetap memakai nama kelas penuh.

## K-12 — Rute `login` bernama

Middleware `auth` pada rute web bersama (mis. `tautan.buka`) memerlukan rute bernama `login`. `routes/web.php` mendefinisikan `/masuk` yang mengalihkan ke halaman masuk panel admin (`filament.admin.auth.login`); pengalihan ke panel yang benar tetap oleh `ArahkanKePanelSesuaiPeran` setelah login.

## K-13 — Kolom `pesan` pada `riwayat_impor`

Tabel `riwayat_impor` memiliki kolom `pesan` (TEXT, nullable) untuk alasan impor gagal seluruhnya (mis. "Impor lain sedang berjalan." atau galat berkas). Berkas galat baris hanya diunduh sebelum `kedaluwarsa_at`; pembersihan berkas kedaluwarsa dijadwalkan di penjadwal.

## K-14 — Akses tautan bukti: pimpinan tidak termasuk

Pimpinan tidak diberi akses ke tautan bukti karena BR-26 membatasi data pribadi. `TautanBerkasPolicy` mengizinkan: anggota, dosen pembimbing (bimbingan), admin-fakultas (`prestasi.verifikasi`/`prestasi.buat-atas-nama`), dan admin-prodi yang prodinya terlibat. Pimpinan tetap melihat prestasi (daftar/agregat) tetapi tidak membuka tautan bukti. Ubah di `TautanBerkasPolicy::view` bila fakultas memutuskan lain.

## K-15 — Content-Security-Policy

`HeaderKeamanan` (middleware global) memasang nosniff, `X-Frame-Options: SAMEORIGIN`, Referrer-Policy, Permissions-Policy, HSTS (hanya `production`), dan CSP: `default-src 'self'`; `img-src 'self' data: https://lh3.googleusercontent.com`; `frame-src https://drive.google.com https://docs.google.com`; `style-src` menambah `'unsafe-inline'` dan fonts.bunny.net; `script-src 'self' 'unsafe-inline' 'unsafe-eval'` (Livewire/Alpine/Filament tidak mendukung nonce tanpa konfigurasi tambahan; jangan diperketat tanpa menguji seluruh panel). CSP dilewati di lingkungan `local` agar server dev Vite berfungsi. Bila panel tampil rusak di staging, periksa konsol browser untuk pelanggaran CSP dan longgarkan direktif seperlunya.

## K-16 — Subpath aplikasi dari satu `APP_URL`

Aplikasi dapat dipasang di `https://<host>/<app>`. Awalan diturunkan dari path `APP_URL`: path cookie sesi (bila `SESSION_PATH` kosong), pembuangan awalan di `public/index.php` bila proxy meneruskannya utuh, dan `rewrite` nginx pada image `web` (`docker/nginx/10-awalan.sh`). Proxy yang memotong awalan (disarankan) dan yang meneruskannya sama-sama didukung.

## K-17 — Kata sandi, sesi, dan MFA

Pengguna mengganti kata sandi di halaman Profil (sandi lama diperiksa, kebijakan minimal 12 karakter berhuruf dan angka). Penggantian mengeluarkan semua sesi lain (`AuthenticateSession` + `logoutOtherDevices`) dan tidak pernah mencatat kata sandi di log. Super-admin dapat mewajibkan ganti sandi per akun atau massal; akun itu hanya dapat membuka halaman profil dan keluar sampai sandi diganti. MFA aplikasi authenticator dimatikan lewat `MFA_AKTIF=false`; kodenya utuh dan dinyalakan kembali dengan `MFA_AKTIF=true`.

## K-18 — Masuk sebagai pengguna lain

Fitur darurat khusus super-admin tanpa dependensi tambahan: tautan bertanda tangan berumur 1 menit yang terikat pada super-admin penekannya, tidak dapat bersarang, tidak untuk diri sendiri atau akun nonaktif, dan tidak mengubah `last_login_at`. Awal dan akhir sesi dicatat, aktivitas selama sesi membawa `impersonator_id`, dan banner permanen menampilkan tombol kembali. Kewajiban MFA dan ganti sandi pada akun target tidak diberlakukan selama sesi ini, dan kata sandi target tidak dapat diganti.

## K-19 — Nama database (selesai) dan port lokal (menunggu keputusan pemilik)

Keputusan 16 Oktober 2026 menyamakan PHP 8.5 dan MariaDB 10.11+ (kolasi `utf8mb4_unicode_ci`) di seluruh dokumen. Dari dua pertentangan antardokumen, satu sudah diputuskan:

- **Nama database — SELESAI (keputusan pemilik 16 Oktober 2026)**: nama seragam `db_<app>` / `db_<app>_testing` (STANDAR-TEKNIS §2.1), yaitu `db_puspresma` dan `db_puspresma_testing`. `CLAUDE.md`, `.persiapan/vibecoding/CLAUDE.md`, dan `04-PROMPT-BERTAHAP.md` telah disamakan (sebelumnya `puspresma` / `puspresma_testing` / `puspresma_test`). Nama pengguna DB tidak diubah.
- **Port lokal — TETAP TERBUKA**: `CLAUDE.md`, `.persiapan/vibecoding/CLAUDE.md`, dan `04-PROMPT-BERTAHAP.md` memakai 8016; README, KEPUTUSAN (K-03), dan DEPLOY memakai 8032. Fakta: STANDAR-TEKNIS §2.1 mengalokasikan 8016 untuk puspresma. Belum diputuskan; tidak diseragamkan.

## K-20 — UUID native dan MariaDB native di produksi (keputusan 16 Oktober 2026)

- **Tipe UUID**: kolom `uuid()` / `foreignUuid()` / `uuidMorphs()` mengikuti Blueprint Laravel 13, yaitu tipe `UUID` native MariaDB (UUIDv7 dibuat aplikasi lewat `HasUuids`). Dokumen tidak lagi menyebut `CHAR(36)` sebagai tipe kolom; `char('id', 36)` dan `binary(16)` eksplisit dilarang. Gerbang uji (Pest, F1.5): kolom `id` bertipe `uuid`/`char(36)`; 100 id UUIDv7 terurut; FK dan morph berfungsi.
- **Skema**: di `03-SKEMA-DATABASE.md`, `PenyediaTautan` dan `StatusCekTautan` pada `tautan_berkas` adalah `ENUM` MariaDB (sesuai migrasi `create_tautan_berkas_table` dan STANDAR-TEKNIS §1a); enum lain tetap `VARCHAR`. Pertentangan di dokumen itu sudah diselesaikan.
- **Produksi**: MariaDB native di host lewat `host.docker.internal` (`extra_hosts` host-gateway); compose produksi tidak boleh memuat container database. Service `db` profil `db-lokal` di `docker-compose.prod.yml` dihapus dan komentar `backup.sh` diperbaiki (K-21). Service container MariaDB di CI bersifat sementara, hanya untuk CI.
- **PHP 8.5**: `composer.json` `^8.5` dan Dockerfile 8.5; tidak ada pekerjaan.

## K-21 — Pelaksanaan Profil Ringan di kode: pilihan teknis (16 Oktober 2026)

Pilihan saat menerapkan K-05, K-06, dan K-20 pada kode (cabang `feat/profil-ringan`). **Menunggu persetujuan pemilik**; butir (!) berbeda dari teks standar atau menyentuh kebijakan.

1. Nama antrean logis lama dipertahankan di `config/puspresma.php` dan dipetakan ke dua antrean fisik: `notifikasi` dan `berkas` ke `default`; `ekspor` dan `impor` ke `berat`. Kode job tidak berubah.
2. Job berat memakai koneksi `database-berat` yang bisa diatur lewat `PUSPRESMA_KONEKSI_BERAT` (bawaan `database-berat`, `sync` di phpunit agar tes ekspor ujung ke ujung tetap berjalan inline).
3. (!) Worker `default` memakai `--timeout=60` (standar §1b) dan `--memory=256` (tambahan saya agar job tidak menghabiskan RAM server 3,8 GiB). **Belum diverifikasi**: job di antrean `default` yang dulu berjalan lebih lama di Horizon (mis. `KirimKompetisiBaruTerbit` yang menotifikasi banyak pengguna) dapat terpotong pada 60 detik. Perlu diukur di staging.
4. `/api/health` mempertahankan bentuk JSON lama (`status`, `checks.database`, `checks.queue_size`); kunci `redis` dihapus dan ditambah `job_tertunda`, `job_gagal_24j`, `detak_penjadwal`. 503 hanya bila basis data gagal. Belum ada konsumen eksternal yang diketahui (integrasi FKIP Edu belum dibangun).
5. (!) `Cache::lock` (5 titik) dipertahankan di atas cache `database` (tabel `cache_locks`); standar §1b menganjurkan transaksi + `lockForUpdate`. Penggantian ditunda sampai ada uji konkurensi yang membuktikannya.
6. Izin `horizon.akses` dihapus dari seeder dan dari data lewat migrasi baru; pembersihan cache izin dibuat tidak fatal karena `migrate` berjalan dari image baru sebelum container diganti.
7. (!) CI yang aktif diubah: service dan ekstensi Redis dihapus. Healthcheck nginx diperketat menjadi `"status":"ok"` persis.
8. (!) `mem_limit` (app 512m, nginx 64m, scheduler 768m, backup 128m) adalah perkiraan awal; scheduler 768m menampung worker 256m dan 512m sekaligus. Wajib dibuktikan dengan `docker stats`.
9. Port lokal (8016 vs 8032) tetap terbuka (K-19). Peralihan produksi dan pembatalannya ada di `docs/DEPLOY.md`; `deploy.sh --rollback` saja tidak cukup untuk kembali ke Redis/Horizon.

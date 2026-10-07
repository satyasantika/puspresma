# Keputusan & Pengecualian

## K-01 — MariaDB sebagai pengganti MySQL 8.4

Basis data memakai MariaDB 10.11 native di host (`db_puspresma`, `db_puspresma_testing`; koneksi `DB_CONNECTION=mariadb`, `DB_HOST=host.docker.internal`). Kolom `uuid()` Laravel dipetakan ke tipe native `uuid` MariaDB. Collation `utf8mb4_unicode_ci` (bawaan Laravel untuk MariaDB) menggantikan `utf8mb4_0900_ai_ci` yang hanya ada di MySQL 8. Pengguna dan kata sandi basis data sama dengan proyek lain di mesin ini (dibaca dari `.env` lokal, tidak pernah di-commit).

## K-02 — Compose pusat, bukan `docker-compose.yml` di repo

Lingkungan lokal memakai compose pusat `~/code/docker-compose.yml` (service `puspresma-php` PHP 8.5 dan `puspresma-nginx` di port host 8032, jaringan `laranet`; konfigurasi nginx `~/code/docker/nginx/puspresma.conf`). Repo ini tidak memuat `docker-compose.yml` dev maupun `docker/php/Dockerfile` dev; yang ada hanya `Dockerfile` dan `docker-compose.prod.yml` untuk produksi (pola proyek surat). Perintah: `docker exec -w /var/www/html puspresma-php <cmd>` (alias `psp`). Node tidak ada di container sehingga `npm` dijalankan di host. Setelah container `puspresma-php` dibuat ulang, `puspresma-nginx` perlu `nginx -s reload` agar memakai IP upstream baru (gejala: 502).

## K-03 — Versi dan port

PHP 8.5 (perintah pemilik; `composer.json` `"php": "^8.5"`, CI dan Dockerfile produksi 8.5) menggantikan PHP 8.4 pada dokumen paket. Port lokal 8032 (bukan 8016) dan loopback produksi 8032. Gateway lokal `:8080` tidak tersedia; subpath diuji lewat konfigurasi override di test.

## K-04 — Bahan vibecoding tidak di-commit

`vibecoding/`, `CLAUDE.md` root, dan PDF regulasi di root di-gitignore (mengikuti keadaan akhir proyek surat); tetap dipakai agen lokal. Langkah F0.1 butir "salin ke docs/vibecoding" tidak dilakukan.

## K-05 — Redis

Redis memakai container bersama `redis` (DB 40 default, 41 cache, 42 antrean; prefiks `puspresma_`). Kata sandi dibaca dari `.env` lokal. Test memakai driver array/sync dan tidak menyentuh Redis.

## K-06 — Service Horizon dan scheduler di compose pusat

Ditambahkan ke `~/code/docker-compose.yml` (di luar repo; pola `kerjasama-scheduler`): `puspresma-horizon` (`php artisan horizon`, `stop_grace_period: 60s`) dan `puspresma-scheduler` (`schedule:work`), memakai image PHP 8.5 dan kode yang sama. Cadangan berkas compose sebelum diubah disimpan agen di scratchpad sesi. Test tidak menyentuh Redis: `phpunit.xml` memaksa cache/sesi `array` dan antrean `sync`, sehingga pengaturan Redis diuji lewat `.env.example` dan konfigurasi koneksi (`RedisHorizonTest`).

## K-07 — CI, deploy, dan berkas produksi

`ci.yml` memakai MariaDB 10.11 (bukan MySQL 8.4) dan PHP 8.5 mengikuti perintah pemilik. `deploy.yml` mengikuti pola proyek sigap (`appleboy/ssh-action`, `~/docker-apps/deploy.sh puspresma`); repositori GitHub dan secret dibuat manusia (tidak ada remote pada sesi ini, tidak ada push). `Dockerfile`, `docker-compose.prod.yml`, dan `docker/` disalin dari pola surat sejak F1.8 agar CI/deploy konsisten; Gotenberg tidak dipakai, langkah `filament:upgrade` ditambahkan pada F2.1 saat Filament dipasang, dan daftar tabel `uji-pulih.sh` diperluas saat tabel domain ada.

## K-08 — Pemisahan tugas super-admin pada verifikasi

Super-admin menerima seluruh permission di seeder (PRD §3), tetapi `Gate::before` menolak tiga ability Policy `verifikasi`, `loloskanProdi`, dan `bukaVerifikasi` untuknya. Policy fase berikutnya wajib memakai nama ability tersebut (bukan `can('prestasi.verifikasi')` langsung) agar pengecualian berlaku, karena pemeriksaan permission spatie berjalan lebih dahulu daripada `Gate::before` aplikasi. Gerbang Horizon memakai peran `super-admin` (perintah pemilik), bukan permission `horizon.akses`; permission itu tetap dibuat untuk kelengkapan.

## K-09 — Pengalihan panel sebelum Authenticate

`ArahkanKePanelSesuaiPeran` didaftarkan lewat `prependToPriorityList` agar berjalan sebelum `Authenticate` Filament (yang menjawab 403 bagi pengguna tanpa akses panel). Pengalihan hanya untuk permintaan GET/HEAD. Tabel `notifications` (diperlukan `databaseNotifications()` panel) ditambahkan pada langkah F2.2 karena terlewat di F2.1.

## K-10 — Retensi log aktivitas dan batas login

Retensi log aktivitas `clean_after_days` = 730 hari (di activitylog v5 kuncinya `clean_after_days`, bukan `delete_records_older_than_days`); angka ini asumsi yang perlu ditetapkan fakultas. Limiter `login` (5/menit per surel+IP) diterapkan pada halaman masuk bersama kedua panel (`App\Filament\Pages\Auth\Masuk`) dan dibersihkan saat login berhasil; log `auth` hanya menyimpan surel, IP, dan user agent. Model `User` hanya mencatat `name`, `email`, `is_active`, dan peristiwa peran (`peran_ditambah`/`peran_dicabut`) lewat event spatie permission (`events_enabled` = true).

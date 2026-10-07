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

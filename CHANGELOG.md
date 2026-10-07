# Catatan Perubahan

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan [Semantic Versioning](https://semver.org/lang/id/).

## [Belum dirilis]

## [0.1.0] - 2026-10-07

Fase 1: fondasi Laravel.

### Ditambahkan
- Laravel 13 di PHP 8.5 pada lingkungan Docker FKIP (compose pusat `puspresma-php`, `puspresma-nginx`, port 8032; MariaDB native di host; Redis bersama DB 40/41/42), konfigurasi locale `id`, zona waktu `Asia/Jakarta`, mode ketat Eloquent, dan `config/puspresma.php`.
- Redis untuk cache, sesi, dan antrean; Laravel Horizon dengan tiga supervisor (`notifikasi`, `umum`, `berat`); service `puspresma-horizon` dan `puspresma-scheduler` di compose pusat.
- Pest 4, Larastan level 6, Pint, Laravel Boost, dan uji arsitektur (enum, Actions, tanpa `dd`/`dump`, UUID, subpath).
- Primary key UUIDv7 pada seluruh tabel aplikasi (`users`, `sessions`).
- Siap subpath `https://supportfkip.unsil.ac.id/puspresma` (root URL, proxy tepercaya, cookie `puspresma_session`, aset Vite berawalan).
- Endpoint `GET /api/health` dan versi aplikasi pada footer halaman awal.
- CI GitHub Actions (`ci.yml`: Pint, Larastan, Pest dengan MariaDB dan Redis) dan deploy otomatis (`deploy.yml`, SSH ke `~/docker-apps/deploy.sh puspresma`); `Dockerfile`, `docker-compose.prod.yml`, dan `docs/DEPLOY.md`.
- Dokumentasi keputusan teknis di `docs/KEPUTUSAN.md`.

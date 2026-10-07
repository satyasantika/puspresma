# Catatan Perubahan

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan [Semantic Versioning](https://semver.org/lang/id/).

## [Belum dirilis]

## [0.2.0] - 2026-10-08

Fase 2: autentikasi, peran & audit.

### Ditambahkan
- Panel Filament 5 `admin` (`/admin`, indigo) dan `portal` (`/portal`, emerald) dengan notifikasi basis data dan halaman masuk bersama (`Masuk`) yang menolak akun nonaktif ("Akun Anda dinonaktifkan").
- Kolom `users` untuk Puspresma (`google_id`, `npm`, `nidn`, `nip`, `nomor_wa`, `notif_wa_aktif`, `is_active`, `last_login_at`) dan pencatatan login terakhir.
- Peran dan izin (spatie/laravel-permission, UUID): `super-admin`, `admin-fakultas`, `admin-prodi`, `pimpinan`, `dosen`, `mahasiswa` beserta seeder idempoten dan `SuperAdminSeeder` (kata sandi acak dari `SEED_SUPERADMIN_*`); akses panel menurut peran dengan pengalihan otomatis ke panel yang benar.
- Gerbang Horizon hanya untuk `super-admin`; `super-admin` ditolak pada ability verifikasi prestasi (`verifikasi`, `loloskanProdi`, `bukaVerifikasi`).
- Jejak audit (spatie/laravel-activitylog, UUID): log `auth` (login, login gagal, logout dengan IP dan user agent), log perubahan pengguna dan peran tanpa kata sandi/rahasia MFA, dan Resource Log Aktivitas (grup Sistem, izin `audit.lihat`).
- MFA aplikasi authenticator (TOTP + kode pemulihan) di panel admin, wajib bagi `super-admin` dan `admin-fakultas`.
- Pembatas login 5 percobaan per menit per surel+IP pada kedua panel.
- Pengelolaan pengguna (grup Sistem): buat/ubah/nonaktifkan akun, atur peran, tautan atur kata sandi lewat surel antrean, reset MFA oleh super-admin; perlindungan terhadap hapus diri sendiri dan pencabutan super-admin terakhir.
- Uji arsitektur: setiap Resource Filament memiliki Policy.

### Diubah
- Gerbang `viewHorizon` memakai peran `super-admin` (sebelumnya hanya lingkungan local).

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

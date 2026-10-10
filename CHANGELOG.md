# Catatan Perubahan

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan [Semantic Versioning](https://semver.org/lang/id/).

## [Belum dirilis]

## [0.4.0] - 2026-10-10

Fase 4: pengajuan prestasi & bukti.

### Ditambahkan
- Tabel `prestasi`, `prestasi_anggota`, `prestasi_anggota_eksternal`, `riwayat_status_prestasi` (UUIDv7, FULLTEXT nama kegiatan + penyelenggara), enum status/bentuk/bidang/moda/peran/konfirmasi pembimbing, model, factory, `NormalisasiTeks` dan scope `melibatkanProdi`/`milikMahasiswa`.
- Portal mahasiswa "Prestasi Saya": wizard 4 langkah (kegiatan, capaian & tingkat dengan validasi kondisional nasional/internasional, tim & pembimbing, tautan bukti), peringatan BR-22, Action `SimpanPrestasi`, `PrestasiPolicy`.
- Tim lintas prodi (kredit penuh), anggota luar FKIP, dosen pembimbing (`SimpanAnggotaTim`, event `AnggotaTimDitambahkan`).
- Tautan bukti tanpa unggah ke server (`SimpanTautanBukti`/`UbahTautanBukti`/`HapusTautanBukti`/`SinkronTautanBukti`, maks 10, `tampil_publik` hanya foto), `TautanBerkasPolicy` lengkap, petunjuk berbagi (BR-31), pratinjau Drive di halaman lihat.
- `TransisiStatus` (lock Redis + `lockForUpdate`, tabel transisi BR-07, catatan ≥ 10 karakter BR-08, riwayat, event `StatusPrestasiBerubah`), `AjukanPrestasi` (kelengkapan BR-04, nomor `PRS-YYYY-NNNNN` ber-lock, status konfirmasi pembimbing) dan `TarikKembaliPrestasi`; aksi Ajukan/Tarik Kembali dan riwayat status di portal.
- Uji arsitektur: status prestasi hanya diubah di `app/Actions/Prestasi`; uji alur end-to-end pengajuan.

### Diubah
- `TautanBerkasPolicy` mengikuti matriks PRD (pimpinan tidak membuka tautan bukti; K-14).

## [0.3.0] - 2026-10-10

Fase 3: data master, impor, tautan berkas & login Google.

### Ditambahkan
- Master akademik: `prodi`, `mahasiswa`, `dosen` (UUIDv7, soft delete, activitylog; `nomor_hp` tidak dicatat nilainya), `users.prodi_id`, `ProdiSeeder` dari CSV (contoh bertanda CONTOH) dan `ProdiScope` untuk membatasi admin-prodi ke prodinya.
- Master klasifikasi prestasi: jenis kegiatan, kategori lomba, tingkat, capaian (kolom `kode_simkatmawa`), seeder idempoten dan `OpsiMaster` ber-cache yang dibuang `MasterObserver`.
- Resource Filament grup Master (`MasterPolicy`; Mahasiswa/Dosen dengan cakupan prodi, kolom surel hanya untuk `master.kelola`, aksi "Buat akun").
- Impor mahasiswa dan dosen dari XLSX/CSV lewat antrean `impor` (lock, upsert per 500 baris, berkas galat ≤ 24 jam, berkas sumber selalu dihapus), riwayat impor dan notifikasi `ImporSelesai`.
- Pengaturan sistem berbasis kunci-nilai (`App\Services\Pengaturan`, cache, activitylog lama → baru) dan halaman Pengaturan Sistem.
- Bobot poin (`bobot_poin`, DECIMAL) per jenis × tingkat × capaian dengan masa berlaku, `PenghitungPoin`, Resource Bobot Poin dan halaman Matriks Bobot Poin, `BobotPoinContohSeeder` manual.
- Fondasi tautan berkas tanpa unggahan ke server: `tautan_berkas`, `TautanBerkasValid`, `PenyimpananBerkas`/`TautanEksternal`, job `PeriksaTautanBerkas`, route `tautan.buka` (Policy + activitylog), komponen Blade `tautan-berkas` dan `pratinjau-drive`, uji arsitektur "tanpa unggahan selain impor".
- Login Google akun kampus (opsional, `GOOGLE_LOGIN_ENABLED`): hanya domain kampus dan data yang ada di master, tanpa registrasi mandiri; peran wajib MFA ditolak.

### Diubah
- Rute bernama `login` (`/masuk`) mengalihkan tamu ke halaman masuk panel.
- Alias morph `prestasi` dan `kompetisi` memakai `morphMap` (lihat K-11 di `docs/KEPUTUSAN.md`).

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

# Catatan Perubahan

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan [Semantic Versioning](https://semver.org/lang/id/).

## [Belum dirilis]

## [0.8.0] - 2026-10-10

Fase 8: laporan, dasbor & ekspor.

### Ditambahkan
- Dasbor statistik (`StatistikPrestasi`, `Cache::flexible` per versi): ringkasan, per prodi anggota (BR-02), tingkat, jenis, capaian, tren bulanan/tahunan; filter tahun dan prodi (admin-prodi dikunci); widget ringkasan, grafik, tabel terbaru, dan kartu antrean.
- Ekspor: `EksporService` (≤ 2.000 baris di-stream tanpa berkas; lebih besar lewat antrean `ekspor` ke `storage/app/tmp` ≤ 24 jam dengan lock anti klik ganda dan pembatas 10/jam), `berkas_ekspor`, unduhan ber-Policy (410 bila kedaluwarsa), halaman Laporan dan Berkas Ekspor, laporan LAP-01 (rekap), LAP-02 (per anggota), LAP-06 (peringkat poin), LAP-09 (katalog), LAP-07 (rekap pribadi PDF mahasiswa).
- LAP-03 statistik akreditasi (TS-2/TS-1/TS) dan LAP-04 rekap IKU (mahasiswa unik; parameter di Pengaturan), keduanya XLSX multi-sheet dan PDF.
- Ekspor SIMKATMAWA dengan pemetaan kolom di `config/simkatmawa.php`, URL bukti dari snapshot verifikasi, pratinjau, dan penandaan "dilaporkan" (massal/pembatalan bercatatan).
- Surat Keterangan Prestasi ber-QR: penerbitan bernomor ber-lock, cetak PDF on-the-fly (watermark "DICABUT"), pencabutan, halaman verifikasi publik `/verifikasi/{id}` ber-cache tanpa data pribadi, serta portal "Surat Keterangan Saya".
- `DemoSeeder` (sisipan massal) dan `docs/kinerja.md` berisi hasil uji kinerja 10.000 prestasi.

### Diperbaiki
- Ekspor di atas 500 baris tidak lagi melewatkan baris (iterasi `lazyById` dengan urutan kueri yang tidak konsisten).

## [0.7.0] - 2026-10-10

Fase 7: notifikasi & penjadwal.

### Ditambahkan
- Notifikasi (selalu `ShouldQueue`, antrean `notifikasi`, `afterCommit`): prestasi diajukan/perlu perbaikan/terverifikasi/ditolak, permintaan konfirmasi dan penyangkalan pembimbing, kandidat duplikat, anggota tim ditambahkan, kompetisi baru terbit (per chunk 200), tautan bukti tidak dapat diakses (BR-30), berkas ekspor siap; listener per peristiwa dengan penerima yang tepat dan anggota tanpa akun dilewati (tercatat di log).
- Kanal WhatsApp Fonnte (`FonnteClient`, job `KirimPesanWhatsApp` terpisah dengan 3× percobaan, token tidak pernah dicatat), trait `BisaWhatsApp`, normalisasi nomor 08xx → 62xx, halaman Preferensi Notifikasi (portal dan admin) dengan pesan uji dibatasi 3/jam.
- Penjadwal: arsip kompetisi, pengingat tenggat H-3/H-1 (tanpa ganda), ringkasan antrean verifikasi, pengingat pembimbing, pembersihan berkas sementara > 24 jam, pemeriksaan ulang mingguan semua tautan (berprioritas, berjeda), `horizon:snapshot`, `activitylog:clean`, `queue:prune-failed`; dokumentasi di README.
- Uji arsitektur: setiap Notification mengantre.

## [0.6.0] - 2026-10-10

Fase 6: katalog kompetisi & galeri publik.

### Ditambahkan
- Katalog kompetisi: tabel `kompetisi`, `kompetisi_prodi`, `kompetisi_minat`, FK `prestasi.kompetisi_id`, deskripsi disanitasi (`mews/purifier`, profil `deskripsi`), poster sebagai tautan, KompetisiResource (terbitkan, arsipkan, duplikat; admin-prodi hanya sasaran prodinya), arsip otomatis (`ArsipkanKompetisiKedaluwarsa`).
- Halaman publik `/kompetisi` dan detail (cache per versi katalog, filter, pencarian, tenggat 3 hari), beranda dengan kompetisi terdekat dan prestasi terbaru, katalog dan penanda minat di portal, prapengisian prestasi dari katalog (`kompetisi_id`).
- Persetujuan publikasi opt-in per anggota (`UbahPersetujuanPublikasi`, tercatat IP/user agent/versi teks), `Prestasi::tampilDiGaleri()`/`fotoPublik()`, komponen `x-foto-drive` (foto dimuat dari `lh3.googleusercontent.com`, bukan server), Persetujuan Publikasi di portal beserta alasan belum tampil.
- Galeri publik `/galeri` dan detail (hanya data publik, cache 10 menit dengan pembuangan seketika), kebijakan privasi galeri, moderasi (sembunyikan/tampilkan dengan alasan), pesan saat galeri dinonaktifkan, pembatas laju `galeri` dan `katalog-publik`.
- Layout publik dengan navigasi, tautan lewati-ke-konten, label formulir, dan fokus terlihat.

### Diubah
- Perubahan status pemeriksaan tautan foto dan pengaturan galeri menaikkan `galeri:versi`.

## [0.5.0] - 2026-10-10

Fase 5: verifikasi, duplikat, pembimbing & poin.

### Ditambahkan
- Action verifikasi (`LoloskanTahapProdi`, `KembalikanUntukPerbaikan`, `TolakPrestasi`, `VerifikasiPrestasi`, `BukaVerifikasiPrestasi`) di atas `TransisiStatus`, `PrestasiPolicy` lengkap (tahap prodi opsional BR-09, cakupan prodi BR-15, super-admin ditolak pada seluruh ability verifikasi) dan `snapshot_tautan_verifikasi` yang tidak dapat diubah (BR-28/29).
- Deteksi duplikat (`DeteksiDuplikatService`, job `DeteksiDuplikatPrestasi`, `kandidat_duplikat`, `TinjauKandidatDuplikat`) yang memblokir verifikasi sampai ditinjau (BR-10).
- Panel admin: antrean prestasi dengan tab, ikon peringatan, pratinjau tautan bukti, relation manager (kandidat duplikat, riwayat, snapshot), aksi verifikasi, ganti tautan prestasi terverifikasi (BR-30), input atas nama mahasiswa dan daftar global kandidat duplikat.
- Konfirmasi dosen pembimbing (`KonfirmasiPembimbingan`/`SangkalPembimbingan`, resource Konfirmasi Pembimbing dan Prestasi Bimbingan dengan badge).
- Poin prestasi: `HitungPoinPrestasi` (snapshot bcmath, mode penuh/dibagi), hitung ulang massal, Poin Saya (per tahun dan tahun akademik), Peringkat Poin admin.

### Diubah
- Event `StatusPrestasiBerubah` dikirim setelah commit; ability `kembalikan`, `tolak`, `gantiTautan`, `tinjauDuplikat` ikut dikecualikan bagi super-admin.

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

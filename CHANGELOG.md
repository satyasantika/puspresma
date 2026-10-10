# Catatan Perubahan

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan [Semantic Versioning](https://semver.org/lang/id/).

## [Belum dirilis]

### Ditambahkan
- Landing page yang menjelaskan sistem (alur, fitur, statistik publik, kompetisi, prestasi terbaru, dan panduan per peran); tema yang sama dipakai halaman masuk kiri-kanan, panduan HTML, dan seluruh halaman galat HTTP (400–504) yang mandiri.
- Profil pengguna (nama, nomor WhatsApp) dan ganti kata sandi untuk semua akun; penggantian mengeluarkan semua sesi lain. Super-admin dapat mewajibkan ganti sandi per akun atau massal.
- Impor pengguna massal lewat salin-tempel (pratinjau, upsert menurut surel, sandi awal opsional) dan pengaturan program studi pengguna (diperlukan admin prodi).
- Masuk sebagai pengguna lain untuk super-admin (darurat): tautan bertanda tangan, banner permanen, tercatat di log aktivitas.
- Dukungan subpath `https://<host>/<app>` dari satu `APP_URL`, termasuk proxy yang meneruskan awalan.
- Rilis otomatis: `scripts/deploy.sh` (cadangan DB, build bertag commit, migrasi dengan image baru, health check, rollback, mode `--tarik` untuk cron) dan job `deploy` di `ci.yml` yang menunggu uji hijau.

### Diubah
- Akun super-admin awal `fkip@unsil.ac.id` (sandi awal dari `SEED_SUPERADMIN_PASSWORD`; segera ganti). MFA dimatikan lewat `MFA_AKTIF=false`; kodenya utuh.
- Panduan super-admin tidak lagi dipublikasikan di halaman publik (tersedia untuk pengguna masuk di Bantuan).
- `deploy.yml` terpisah dihapus (berjalan paralel dengan CI sehingga kode gagal uji tetap terdeploy); skrip rilis kini ada di repo, bukan di `~/docker-apps/deploy.sh`.

## [1.0.0] - 2026-10-10

Rilis produksi pertama: pengerasan dan rilis.

### Ditambahkan
- Panduan pengguna per peran (`docs/panduan/*.md`) dirender di `/bantuan/{peran}`; menu **Bantuan** di kedua panel.
- Backup harian database terenkripsi (`spatie/laravel-backup`), jadwal, dan `docs/PEMULIHAN.md`.
- Dockerfile/compose produksi: `APP_VERSION`, `RUN_MIGRATIONS` opt-in, healthcheck `/api/health`, `icons:cache`.

### Diubah
- Optimasi kueri antrean admin dan Prestasi Saya (37/40 → ≤ 15 kueri), dijaga `JumlahKueriTest`.

### Diperbaiki
- Tidak ada temuan UAT yang diserahkan penguji manusia pada tahap UAT; daftar temuan masih kosong. Temuan yang masuk kelak dicatat di sini beserta test regresinya.

### Tambahan
- Header keamanan (CSP kecuali lokal, HSTS hanya produksi), matriks otorisasi per peran, uji XSS, dan uji arsitektur.
- Halaman panduan HTML per peran dengan tangkapan layar Playwright di `public/panduan/` (ditautkan dari landing page dan menu pengguna), `PanduanSeeder` (hanya lokal), skrip `tests/panduan/` dan `composer panduan`.

### Ringkasan fitur v1.0.0
- Pengajuan prestasi individu/tim lintas prodi dengan tautan bukti Google Drive (tanpa unggah berkas), daftar putih domain, pemeriksaan keteraksesan, dan snapshot URL saat terverifikasi.
- Verifikasi bertahap (prodi opsional, fakultas), transisi status tunggal berkunci, catatan wajib, riwayat dan log aktivitas, deteksi duplikat, konfirmasi dosen pembimbing.
- Poin opsional (bobot per tanggal, DECIMAL, mode penuh/dibagi), katalog kompetisi publik dengan pengarsipan otomatis, galeri publik berbasis persetujuan (UU PDP).
- Dasbor statistik, laporan Excel (rekap, anggota, poin, katalog, akreditasi, IKU), ekspor SIMKATMAWA dengan penandaan, Surat Keterangan Prestasi ber-QR.
- Notifikasi surel/WhatsApp lewat antrean, penjadwal (arsip kompetisi, pemeriksaan tautan mingguan, pengingat), impor Excel/CSV master, login Google opsional (domain kampus), MFA untuk super-admin dan admin-fakultas.
- Docker produksi, backup harian database terenkripsi, panduan pengguna per peran.

### Perlu verifikasi (masih terbuka)
Nilai berikut sengaja dibuat dapat dikonfigurasi dan **tidak** dianggap final:
- Format SIMKATMAWA: pemetaan kolom `config/simkatmawa.php`, satu baris per anggota atau per prestasi, dan apakah SIMKATMAWA menerima tautan bukti atau mensyaratkan berkas; `kode_simkatmawa` jenis, kategori, tingkat, dan capaian.
- Daftar rekognisi pada `CapaianSeeder`, serta kategori/jenis/tingkat pada seeder master terhadap pedoman SIMKATMAWA terbaru.
- Ambang jumlah negara (`ambang_negara_internasional`, bawaan 3) dan jumlah perguruan tinggi (`ambang_pt_nasional`, bawaan kosong); keduanya hanya peringatan.
- Definisi IKU (`iku_tingkat_minimal`, `iku_hanya_juara`) dan format lampiran akreditasi sesuai instrumen LAMDIK.
- Pola email mahasiswa `@student.unsil.ac.id` dan OAuth client Google (UPT TIK).
- Kemampuan akun `@student.unsil.ac.id` berbagi Drive ke domain `unsil.ac.id`, dan daftar putih domain `config/berkas.php`.
- Format nomor surat keterangan (`format_nomor_surat_prestasi`, bawaan `{urut}/PRS/FKIP/{tahun}`) dan penandatangan.
- Kalender akademik (`awal_tahun_akademik_bulan`, bawaan 8).

### Sebelum go-live (butuh tindakan manusia)
- **Data prodi resmi**: `database/seeders/data/prodi.csv` masih berisi dua baris CONTOH; ganti dengan data resmi lalu `db:seed --class=ProdiSeeder`.
- UAT oleh pengguna sebenarnya (belum ada daftar temuan), sosialisasi, dan konfigurasi server produksi: DNS/TLS, reverse proxy, secret CI, `.env` produksi, SMTP, dan OAuth Google bila dipakai.
- Uji pulih backup pada data produksi dan pemantauan di server; pengujian di staging untuk skenario pengajuan, verifikasi, dan ekspor.
- Sudah terpenuhi di kode: Pint, Larastan level 6, dan seluruh test hijau; `DatabaseSeeder` produksi tidak memuat `DemoSeeder`, `BobotPoinContohSeeder`, atau `PanduanSeeder` (yang menolak lingkungan selain `local`); backup dan prosedur pulih ada, dan uji pulih pertama pada DB kosong lolos.

## [0.8.0] - 2026-10-10

Laporan, dasbor & ekspor.

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

Notifikasi & penjadwal.

### Ditambahkan
- Notifikasi (selalu `ShouldQueue`, antrean `notifikasi`, `afterCommit`): prestasi diajukan/perlu perbaikan/terverifikasi/ditolak, permintaan konfirmasi dan penyangkalan pembimbing, kandidat duplikat, anggota tim ditambahkan, kompetisi baru terbit (per chunk 200), tautan bukti tidak dapat diakses (BR-30), berkas ekspor siap; listener per peristiwa dengan penerima yang tepat dan anggota tanpa akun dilewati (tercatat di log).
- Kanal WhatsApp Fonnte (`FonnteClient`, job `KirimPesanWhatsApp` terpisah dengan 3× percobaan, token tidak pernah dicatat), trait `BisaWhatsApp`, normalisasi nomor 08xx → 62xx, halaman Preferensi Notifikasi (portal dan admin) dengan pesan uji dibatasi 3/jam.
- Penjadwal: arsip kompetisi, pengingat tenggat H-3/H-1 (tanpa ganda), ringkasan antrean verifikasi, pengingat pembimbing, pembersihan berkas sementara > 24 jam, pemeriksaan ulang mingguan semua tautan (berprioritas, berjeda), `horizon:snapshot`, `activitylog:clean`, `queue:prune-failed`; dokumentasi di README.
- Uji arsitektur: setiap Notification mengantre.

## [0.6.0] - 2026-10-10

Katalog kompetisi & galeri publik.

### Ditambahkan
- Katalog kompetisi: tabel `kompetisi`, `kompetisi_prodi`, `kompetisi_minat`, FK `prestasi.kompetisi_id`, deskripsi disanitasi (`mews/purifier`, profil `deskripsi`), poster sebagai tautan, KompetisiResource (terbitkan, arsipkan, duplikat; admin-prodi hanya sasaran prodinya), arsip otomatis (`ArsipkanKompetisiKedaluwarsa`).
- Halaman publik `/kompetisi` dan detail (cache per versi katalog, filter, pencarian, tenggat 3 hari), beranda dengan kompetisi terdekat dan prestasi terbaru, katalog dan penanda minat di portal, prapengisian prestasi dari katalog (`kompetisi_id`).
- Persetujuan publikasi opt-in per anggota (`UbahPersetujuanPublikasi`, tercatat IP/user agent/versi teks), `Prestasi::tampilDiGaleri()`/`fotoPublik()`, komponen `x-foto-drive` (foto dimuat dari `lh3.googleusercontent.com`, bukan server), Persetujuan Publikasi di portal beserta alasan belum tampil.
- Galeri publik `/galeri` dan detail (hanya data publik, cache 10 menit dengan pembuangan seketika), kebijakan privasi galeri, moderasi (sembunyikan/tampilkan dengan alasan), pesan saat galeri dinonaktifkan, pembatas laju `galeri` dan `katalog-publik`.
- Layout publik dengan navigasi, tautan lewati-ke-konten, label formulir, dan fokus terlihat.

### Diubah
- Perubahan status pemeriksaan tautan foto dan pengaturan galeri menaikkan `galeri:versi`.

## [0.5.0] - 2026-10-10

Verifikasi, duplikat, pembimbing & poin.

### Ditambahkan
- Action verifikasi (`LoloskanTahapProdi`, `KembalikanUntukPerbaikan`, `TolakPrestasi`, `VerifikasiPrestasi`, `BukaVerifikasiPrestasi`) di atas `TransisiStatus`, `PrestasiPolicy` lengkap (tahap prodi opsional BR-09, cakupan prodi BR-15, super-admin ditolak pada seluruh ability verifikasi) dan `snapshot_tautan_verifikasi` yang tidak dapat diubah (BR-28/29).
- Deteksi duplikat (`DeteksiDuplikatService`, job `DeteksiDuplikatPrestasi`, `kandidat_duplikat`, `TinjauKandidatDuplikat`) yang memblokir verifikasi sampai ditinjau (BR-10).
- Panel admin: antrean prestasi dengan tab, ikon peringatan, pratinjau tautan bukti, relation manager (kandidat duplikat, riwayat, snapshot), aksi verifikasi, ganti tautan prestasi terverifikasi (BR-30), input atas nama mahasiswa dan daftar global kandidat duplikat.
- Konfirmasi dosen pembimbing (`KonfirmasiPembimbingan`/`SangkalPembimbingan`, resource Konfirmasi Pembimbing dan Prestasi Bimbingan dengan badge).
- Poin prestasi: `HitungPoinPrestasi` (snapshot bcmath, mode penuh/dibagi), hitung ulang massal, Poin Saya (per tahun dan tahun akademik), Peringkat Poin admin.

### Diubah
- Event `StatusPrestasiBerubah` dikirim setelah commit; ability `kembalikan`, `tolak`, `gantiTautan`, `tinjauDuplikat` ikut dikecualikan bagi super-admin.

## [0.4.0] - 2026-10-10

Pengajuan prestasi & bukti.

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

Data master, impor, tautan berkas & login Google.

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

Autentikasi, peran & audit.

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

Fondasi Laravel.

### Ditambahkan
- Laravel 13 di PHP 8.5 pada lingkungan Docker FKIP (compose pusat `puspresma-php`, `puspresma-nginx`, port 8032; MariaDB native di host; Redis bersama DB 40/41/42), konfigurasi locale `id`, zona waktu `Asia/Jakarta`, mode ketat Eloquent, dan `config/puspresma.php`.
- Redis untuk cache, sesi, dan antrean; Laravel Horizon dengan tiga supervisor (`notifikasi`, `umum`, `berat`); service `puspresma-horizon` dan `puspresma-scheduler` di compose pusat.
- Pest 4, Larastan level 6, Pint, dan uji arsitektur (enum, Actions, tanpa `dd`/`dump`, UUID, subpath).
- Primary key UUIDv7 pada seluruh tabel aplikasi (`users`, `sessions`).
- Siap subpath `https://supportfkip.unsil.ac.id/puspresma` (root URL, proxy tepercaya, cookie `puspresma_session`, aset Vite berawalan).
- Endpoint `GET /api/health` dan versi aplikasi pada footer halaman awal.
- CI GitHub Actions (`ci.yml`: Pint, Larastan, Pest dengan MariaDB dan Redis) dan deploy otomatis (`deploy.yml`, SSH ke `~/docker-apps/deploy.sh puspresma`); `Dockerfile`, `docker-compose.prod.yml`, dan `docs/DEPLOY.md`.
- Dokumentasi keputusan teknis di `docs/KEPUTUSAN.md`.

# 05 — Uji Penerimaan Puspresma

Dipakai pada F9.5 (UAT) dan F9.7 (go-live). Siapkan data uji: 2 prodi (A, B), 1 akun per peran, admin prodi A dan admin prodi B, 2 dosen (X, Y), 4 mahasiswa (M1, M2 prodi A; M3 prodi B; M4 prodi B berstatus lulus), bobot poin contoh, 3 kompetisi (1 terbit, 1 draf, 1 lewat tenggat). Siapkan juga di Google Drive akun uji: 1 sertifikat PDF dibagikan "Siapa saja yang memiliki link", 1 surat tugas dibagikan terbatas, 1 foto penyerahan publik, 1 foto terbatas, dan 1 folder Drive.

Kolom ✓ diisi penguji: ✓ (lulus), ✗ (gagal, tulis nomor temuan), – (tidak diuji).

## 1. Skenario UAT per peran

### 1.1 Mahasiswa

| No | Skenario | Langkah | Hasil diharapkan | ✓ |
|---|---|---|---|---|
| M-01 | Login Google akun kampus | M1 klik "Masuk dengan Google" dengan email `@student.unsil.ac.id` | Masuk ke `/portal`, nama & prodi benar | |
| M-02 | Login Google ditolak | Gunakan akun `@gmail.com` | Pesan "Gunakan akun kampus", tidak masuk | |
| M-03 | Mahasiswa lulus ditolak | M4 login | Ditolak dengan pesan status | |
| M-04 | Isi prestasi individu | M1 isi wizard: kompetisi, nasional, juara 2, tempel tautan Drive sertifikat publik | Draf tersimpan, tautan tampil dengan status "dapat diakses" setelah job cek | |
| M-05 | Validasi tingkat internasional | Pilih internasional tanpa jumlah negara | Ditolak, kolom ditandai wajib | |
| M-06 | Validasi tautan | Tempel `http://…`, tautan `bit.ly`, tautan folder Drive, dan domain di luar daftar putih | Semua ditolak dengan pesan jelas; tidak ada tombol unggah berkas di formulir | |
| M-06b | Tautan terbatas | Tempel tautan surat tugas yang dibagikan terbatas | Tersimpan dengan peringatan "tidak dapat diakses" (tidak memblokir) | |
| M-07 | Prestasi tim lintas prodi | M1 tambah M3 (prodi B) + 1 anggota luar FKIP | Tersimpan; M3 melihat prestasi di portalnya (baca saja) | |
| M-08 | Ajukan belum lengkap | Ajukan tanpa tautan sertifikat | Modal menampilkan daftar kekurangan | |
| M-09 | Ajukan lengkap | Ajukan prestasi M-07 | Status Diajukan, nomor `PRS-YYYY-NNNNN`, form terkunci | |
| M-10 | Tarik kembali | Tarik kembali sebelum diproses | Status kembali Draf | |
| M-11 | Perbaikan | Setelah admin mengembalikan, baca catatan, ubah, ajukan ulang | Catatan tampil; nomor registrasi sama; status Diajukan | |
| M-12 | Persetujuan publikasi | M1 aktifkan persetujuan dan izinkan foto publik tampil, M3 tidak | Galeri menampilkan nama M1, "dan 2 anggota lain", foto dimuat dari `lh3.googleusercontent.com` | |
| M-12b | Foto tidak publik | Ganti foto dengan tautan foto yang dibagikan terbatas | Foto tidak tampil di galeri (placeholder), alasan terlihat di halaman Persetujuan Publikasi | |
| M-13 | Tarik persetujuan | M1 nonaktifkan persetujuan | Prestasi hilang dari galeri pada muat ulang berikutnya | |
| M-14 | Katalog & minat | Buka Katalog, tandai Berminat pada kompetisi terbit | Tersimpan; pengingat H-3 diterima (uji jadwal) | |
| M-15 | Isi dari katalog | Pilih kompetisi dari katalog di wizard | Nama kegiatan & penyelenggara terisi otomatis | |
| M-16 | Poin saya | Setelah terverifikasi (poin aktif) buka Poin Saya | Total poin sesuai matriks | |
| M-17 | Rekap PDF pribadi | Unduh rekap prestasi saya | PDF di-stream, hanya memuat prestasi terverifikasi M1 | |
| M-18 | IDOR | M2 membuka URL detail prestasi M1 / `/tautan/{id}/buka` milik M1 | 403/404 | |
| M-19 | Tampilan ponsel | Isi wizard dan tempel tautan dari aplikasi Drive ponsel ("Salin link") | Semua langkah dapat dipakai tanpa geser horizontal | |
| M-21 | Tautan terkunci | Coba ubah tautan pada prestasi terverifikasi | Tidak tersedia; petunjuk menghubungi admin | |
| M-20 | Notifikasi WA | Aktifkan WA, minta admin mengembalikan prestasi | Pesan WA dan surel diterima | |

### 1.2 Dosen pembimbing

| No | Skenario | Langkah | Hasil diharapkan | ✓ |
|---|---|---|---|---|
| D-01 | Terima permintaan | M1 mengajukan dengan pembimbing X | X menerima surel & badge di portal | |
| D-02 | Konfirmasi | X klik Konfirmasi | Status konfirmasi "Dikonfirmasi" di halaman admin | |
| D-03 | Sangkal | X klik Sangkal tanpa alasan, lalu dengan alasan | Tanpa alasan ditolak; dengan alasan tersimpan, admin & pengaju diberi tahu | |
| D-04 | Batas akses | Y membuka URL permintaan milik X | 403 | |
| D-05 | Prestasi bimbingan | X membuka Prestasi Bimbingan | Hanya prestasi yang ia konfirmasi; tanpa kontak mahasiswa | |
| D-06 | Pengingat | Permintaan menunggu > 7 hari, jalankan jadwal Senin | X menerima pengingat | |

### 1.3 Admin prodi

| No | Skenario | Langkah | Hasil diharapkan | ✓ |
|---|---|---|---|---|
| P-01 | Cakupan data | Admin prodi A membuka Prestasi | Hanya prestasi yang melibatkan mahasiswa prodi A (termasuk tim lintas prodi) | |
| P-02 | IDOR | Admin prodi A membuka URL prestasi yang hanya melibatkan prodi B | 403 | |
| P-03 | Tahap 1 nonaktif | Pengaturan tahap prodi nonaktif | Tombol "Lolos Tahap Prodi" tidak tampil | |
| P-04 | Tahap 1 aktif | Aktifkan; loloskan prestasi pengaju prodi A | Status Lolos Tahap Prodi; admin fakultas diberi tahu | |
| P-05 | Tahap 1 lintas prodi | Admin prodi B mencoba meloloskan prestasi pengaju prodi A (M3 anggota) | Aksi tidak tersedia | |
| P-06 | Tidak bisa verifikasi final | Cari tombol Verifikasi | Tidak ada | |
| P-07 | Mahasiswa prodi | Buka Master → Mahasiswa | Hanya prodi A, tanpa kolom email/HP | |
| P-08 | Kompetisi prodi | Buat kompetisi dengan sasaran prodi A | Berhasil; tidak dapat memilih prodi B | |
| P-09 | Laporan akreditasi | Unduh LAP-03 prodi A | XLSX berisi TS-2, TS-1, TS | |
| P-10 | Ekspor terbatas | Ekspor LAP-01 | Hanya data yang melibatkan prodi A | |

### 1.4 Admin fakultas

| No | Skenario | Langkah | Hasil diharapkan | ✓ |
|---|---|---|---|---|
| F-01 | MFA wajib | Login pertama | Diminta mengatur authenticator | |
| F-02 | Impor mahasiswa | Unggah templat 500 baris dengan 3 baris salah | 497 berhasil, 3 di berkas galat beserta alasan | |
| F-03 | Impor ulang | Unggah berkas sama lagi | Tidak ada duplikasi, data diperbarui | |
| F-04 | Antrean verifikasi | Buka tab Antrean | Badge jumlah, ikon peringatan sesuai kondisi | |
| F-05 | Pratinjau & buka tautan | Buka detail prestasi, klik "Buka berkas" sertifikat | Sematan Drive tampil di samping data; tab baru membuka Drive; tanda "sudah Anda buka" muncul dan tercatat di log | |
| F-05b | Wajib membuka tautan | Klik Verifikasi tanpa centang "telah membuka semua tautan" | Ditolak; peringatan menyebut tautan sertifikat yang belum dibuka | |
| F-06 | Kembalikan | Kembalikan tanpa catatan, lalu dengan catatan | Tanpa catatan ditolak; dengan catatan status Perlu Perbaikan & mahasiswa diberi tahu | |
| F-07 | Duplikat | M1 dan M3 masing-masing mengajukan lomba tim yang sama | Kandidat duplikat muncul; Verifikasi ditolak sampai ditinjau | |
| F-07b | Duplikat tautan | M2 mengajukan prestasi lain dengan tautan sertifikat Drive yang sama (format URL berbeda) | Kandidat `tautan_sama` muncul | |
| F-08 | Pembimbing menunggu | Verifikasi saat konfirmasi menunggu | Wajib centang "tanpa konfirmasi" + catatan | |
| F-09 | Pembimbing disangkal | Verifikasi saat disangkal | Ditolak dengan pesan | |
| F-10 | Verifikasi | Verifikasi prestasi lengkap | Status Terverifikasi; snapshot URL tautan tersimpan; poin terisi (bila aktif); galeri & dasbor terbarui | |
| F-10b | Snapshot | Setelah verifikasi, admin "Ganti tautan" dengan catatan | URL lama → baru di log; snapshot baru alasan `penggantian`; snapshot lama tetap ada | |
| F-10c | Tautan mati | Hapus berbagi sertifikat di Drive, jalankan pemeriksaan tautan mingguan | Status "tidak dapat diakses", pengaju & admin diberi notifikasi, ikon peringatan di antrean | |
| F-11 | Buka verifikasi | Buka verifikasi dengan catatan | Status Perlu Perbaikan, poin dikosongkan, snapshot lama tetap, tercatat di riwayat & log | |
| F-12 | Tolak | Tolak prestasi | Status Ditolak, tidak dapat diubah lagi | |
| F-13 | Input atas nama | Input prestasi arsip 2024 untuk M2 | Tersimpan dengan `dibuat_oleh` admin, M2 menjadi pengaju | |
| F-14 | Katalog | Terbitkan kompetisi dengan tenggat lewat | Ditolak | |
| F-14b | Poster | Isi tautan poster Drive publik | Poster tampil di `/kompetisi/{slug}` dari `lh3.googleusercontent.com` | |
| F-15 | XSS katalog | Tempel `<script>alert(1)</script>` di deskripsi | Skrip hilang setelah simpan; tidak dieksekusi di halaman publik | |
| F-16 | Moderasi galeri | Sembunyikan prestasi dari galeri | Hilang dari `/galeri`; status verifikasi tetap | |
| F-17 | Bobot poin | Simpan matriks periode baru; hitung ulang | Periode lama ditutup; snapshot berubah hanya setelah hitung ulang | |
| F-18 | Ekspor SIMKATMAWA | Ekspor "belum dilaporkan", tandai dilaporkan | Kolom sesuai `config/simkatmawa.php`; kolom URL bukti berisi snapshot verifikasi; filter berikutnya kosong | |
| F-19 | Surat keterangan | Terbitkan surat untuk M1 (2 prestasi), cetak PDF | PDF ber-QR di-stream (tidak tersimpan di server); QR membuka halaman verifikasi | |
| F-20 | Cabut surat | Cabut dengan alasan | Halaman verifikasi menampilkan "Dicabut" | |
| F-20b | Impor sementara | Setelah impor F-02 selesai, periksa `storage/app/tmp/impor` | Berkas sumber sudah terhapus; berkas galat hilang setelah 24 jam | |
| F-20c | Ekspor besar | Ekspor LAP-01 > 2.000 baris | Diproses antrean; berkas terunduh; setelah 24 jam terhapus dan tautan unduh 410 | |
| F-21 | Pengaturan | Ubah ambang duplikat menjadi 0,90 | Berlaku tanpa restart; tercatat di log | |

### 1.5 Pimpinan

| No | Skenario | Langkah | Hasil diharapkan | ✓ |
|---|---|---|---|---|
| K-01 | Dasbor | Buka dasbor tahun berjalan | Statistik per prodi, tingkat, jenis, tren tampil < 1 detik (cache hangat) | |
| K-02 | Hitung lintas prodi | Bandingkan total fakultas dan jumlah per prodi untuk prestasi tim lintas prodi | Prodi A dan B masing-masing +1, total fakultas +1 | |
| K-03 | Laporan IKU | Unduh LAP-04 tingkat minimal nasional, hanya juara | Mahasiswa unik dihitung sekali | |
| K-04 | Baca saja | Coba aksi verifikasi/ubah | Tidak tersedia | |
| K-05 | Privasi | Ekspor LAP-01 | Tanpa email/nomor HP | |

### 1.6 Super admin

| No | Skenario | Langkah | Hasil diharapkan | ✓ |
|---|---|---|---|---|
| S-01 | Kelola pengguna | Buat akun lokal admin prodi B | Akun dapat login dan terbatas ke prodi B | |
| S-02 | Reset MFA | Reset MFA admin fakultas | Admin diminta mengatur ulang; tercatat di log | |
| S-03 | Horizon | Buka `/horizon` | Terbuka; admin fakultas mendapat 403 | |
| S-04 | Tidak memverifikasi | Coba verifikasi prestasi | Tidak tersedia (pemisahan tugas) | |
| S-05 | Health | `GET /api/health` | `status: ok`, versi tampil | |
| S-06 | Backup | Jalankan backup lalu uji pulih di server uji | Database (termasuk tautan & snapshot) pulih; tidak ada berkas pengguna yang perlu dipulihkan | |

### 1.7 Pengunjung publik

| No | Skenario | Langkah | Hasil diharapkan | ✓ |
|---|---|---|---|---|
| U-01 | Galeri | Buka `/galeri`, filter tingkat internasional | Hanya prestasi terverifikasi yang disetujui | |
| U-02 | Privasi galeri | Lihat sumber halaman | Tidak ada NPM, email, tautan sertifikat/surat tugas, atau URL `drive.google.com`; hanya URL gambar `lh3` foto yang disetujui | |
| U-03 | Tautan tanpa login | Akses `/tautan/{id}/buka` tanpa login | Diarahkan ke halaman login; tidak ada pengalihan ke Drive | |
| U-04 | Katalog | Buka `/kompetisi` | Hanya kompetisi terbit & masih dibuka | |
| U-05 | Verifikasi surat | Pindai QR surat | Data minimal surat dan status | |
| U-06 | Rate limit | Muat ulang `/verifikasi/{id}` > 30 kali/menit | 429 | |

## 2. Daftar test Pest minimal per modul

| Modul | Berkas test | Kasus minimal |
|---|---|---|
| fondasi | `KonfigurasiDasarTest`, `HealthTest`, `ArchTest` | timezone/locale; health 200/503; tidak ada dd/dump; enum di app/Enums |
| auth | `Auth/PeranTest`, `Auth/LoginTest`, `Auth/GoogleLoginTest`, `Auth/UserResourceTest` | 6 peran ter-seed; arah panel per peran; throttle login; MFA wajib admin; Google domain/terdaftar/lulus; tidak hapus diri sendiri |
| audit | `Audit/ActivityLogTest` | login tercatat tanpa password; perubahan status & pengaturan tercatat |
| master | `Master/ProdiScopeTest`, `Master/MasterResourceTest`, `Master/OpsiMasterCacheTest` | cakupan admin-prodi; IDOR mahasiswa; cache dihapus saat ubah |
| impor | `Impor/ImporMahasiswaTest`, `Impor/ImporDosenTest` | valid/gagal/berkas galat; berkas sumber terhapus setelah proses; idempoten; lock paralel; 403 non-admin |
| pengaturan | `Pengaturan/PengaturanTest` | cast tipe; cache; log |
| poin | `Poin/BobotPoinTest`, `Poin/HitungPoinTest` | periode berlaku; mode penuh/dibagi; DECIMAL string; nonaktif; hitung ulang |
| prestasi | `Prestasi/FormPrestasiTest`, `Prestasi/AnggotaTimTest`, `Alur/PengajuanPrestasiTest` | validasi kondisional; lintas prodi snapshot; aturan jumlah anggota; policy milik |
| bukti | `Bukti/TautanBerkasValidTest`, `Bukti/PeriksaTautanBerkasTest`, `Bukti/TautanBuktiTest`, `Bukti/BukaTautanTest`, `Bukti/SnapshotTautanTest` | https/daftar putih/pemendek/folder ditolak; ekstraksi `drive_file_id` & `url_normal`; cek keteraksesan tidak memblokir & tanpa unduh isi; IDOR `tautan.buka`; log pembukaan; terkunci setelah terverifikasi; snapshot saat verifikasi & penggantian; mode selain `tautan` exception |
| verifikasi | `Verifikasi/TransisiTest`, `Verifikasi/AntreanVerifikasiTest`, `Alur/VerifikasiPrestasiTest` | semua transisi sah & tidak sah; catatan wajib; tahap prodi; ambil alih; lock nomor; super-admin ditolak |
| duplikat | `Duplikat/DeteksiDuplikatTest` | kemiripan; anggota beda; `drive_file_id`/`url_normal` sama; blokir verifikasi; idempoten |
| pembimbing | `Pembimbing/KonfirmasiTest` | konfirmasi; sangkal wajib alasan; dosen lain 403; reset saat ganti |
| katalog | `Katalog/KompetisiTest`, `Katalog/KatalogPublikTest` | sanitasi; terbit/arsip; cakupan admin-prodi; throttle; minat unik |
| galeri | `Galeri/PersetujuanTest`, `Galeri/GaleriPublikTest`, `Galeri/FotoPublikTest` | opt-in; nama tersamar; tanpa NPM & tanpa URL drive.google.com; invalidasi cache; foto hanya bila tampil_publik + Drive + dapat_diakses; URL `lh3` |
| notifikasi | `Notifikasi/StatusNotifikasiTest`, `Notifikasi/WhatsAppChannelTest`, `ArchTest` | penerima benar; antrean `notifikasi`; WA opt-in; token tidak di log; semua ShouldQueue |
| jadwal | `Jadwal/PenjadwalTest` | pengingat sekali; ringkasan kosong tidak dikirim; arsip kompetisi; bersihkan tmp > 24 jam; periksa tautan mingguan; daftar jadwal |
| laporan | `Laporan/StatistikTest`, `Laporan/EksporRekapTest`, `Laporan/AkreditasiIkuTest` | hitung lintas prodi vs unik; cache; cakupan prodi; ekspor kecil di-stream tanpa berkas; ekspor besar ke tmp & 410 setelah 24 jam; IDOR berkas ekspor; lock klik ganda |
| simkatmawa | `Simkatmawa/EksporSimkatmawaTest` | urutan kolom dari config; URL bukti dari snapshot; hanya terverifikasi; penandaan |
| surat | `Surat/SuratKeteranganTest` | hanya terverifikasi; nomor unik paralel; PDF di-stream tanpa berkas tersimpan; QR on-the-fly; cabut; halaman publik minimal; throttle |
| keamanan | `Keamanan/MatriksOtorisasiTest`, `Keamanan/HeaderKeamananTest`, `Keamanan/XssTest`, `ArchTest` | ≥ 60 kasus matriks peran × aksi; header & CSP (lh3/drive); payload XSS & tautan `javascript:`; tidak ada FileUpload/UploadedFile selain impor |
| kinerja | `Kinerja/JumlahKueriTest` | antrean verifikasi & galeri ≤ 15 kueri |

## 3. Checklist keamanan (OWASP dasar)

| No | Butir | Cara memeriksa | ✓ |
|---|---|---|---|
| K1 | Otorisasi per record di Policy untuk setiap Resource & controller | Test arsitektur policy + `MatriksOtorisasiTest` | |
| K2 | IDOR: URL publik/unduhan memakai UUID, bukan ID berurutan; tetap dicek policy | Coba UUID milik orang lain → 403/404 | |
| K3 | Tidak ada unggah berkas selain impor Excel/CSV (STANDAR-TEKNIS §1a); impor divalidasi `mimes:xlsx,csv` ≤ 10 MB, diproses dari `storage/app/tmp` lalu dihapus | `ArchTest`; cek isi `storage/app` setelah UAT | |
| K4 | Validasi tautan `TautanBerkasValid`: hanya `https://`, domain daftar putih, tolak pemendek URL, folder, `javascript:`/`data:`, dan host tiruan (`drive.google.com.evil.com`) | `TautanBerkasValidTest` | |
| K4b | Tautan hanya tampil ke peran berhak (Policy); pembukaan lewat `tautan.buka` tercatat; tautan sertifikat/surat tugas tidak pernah di halaman publik | `BukaTautanTest`, UAT U-02/U-03 | |
| K4c | Pemeriksaan tautan (`PeriksaTautanBerkas`) tidak mengunduh isi, timeout 10 detik, tanpa kredensial; tidak bisa dipakai sebagai SSRF ke jaringan internal (hanya host daftar putih) | Tinjau job + test | |
| K5 | Foto galeri hanya dari tautan yang disetujui mahasiswa (`tampil_publik`) dan dibagikan publik; mahasiswa diingatkan memakai foto tanpa data lokasi (EXIF tidak dapat dibuang sistem) | UAT M-12, M-12b | |
| K6 | Rate limit: login, Google callback, ajukan, simpan tautan, buka tautan, galeri, katalog, verifikasi surat, ekspor | Test throttle 429 | |
| K7 | CSRF aktif di semua form (bawaan Laravel/Livewire); tidak ada pengecualian route POST publik | Tinjau `bootstrap/app.php` | |
| K8 | XSS: rich text deskripsi kompetisi disanitasi (purifier); semua output Blade `{{ }}`; tidak ada `{!! !!}` tanpa sanitasi; label tautan di-escape | `grep -rn "{!!" resources/` + `XssTest` | |
| K9 | MFA wajib `super-admin` & `admin-fakultas` | UAT F-01 | |
| K10 | Pemisahan tugas: `super-admin` tidak memverifikasi prestasi | UAT S-04 | |
| K11 | Data pribadi (email, HP) hanya untuk peran berwenang; tidak di galeri/verifikasi publik/ekspor pimpinan | UAT U-02, K-05 | |
| K12 | Persetujuan publikasi opt-in, terekam, dapat ditarik (UU PDP); petunjuk berbagi terbatas untuk dokumen ber-data pribadi tampil di formulir | UAT M-12, M-13 | |
| K13 | Activitylog tidak memuat password, token, secret MFA, token Fonnte | Cari di tabel `activity_log` | |
| K14 | Header keamanan (nosniff, frame, referrer, CSP dengan `img-src` lh3 & `frame-src` drive.google.com saja, HSTS di produksi) | DevTools / `curl -I` | |
| K15 | `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, HTTPS wajib | Cek `.env` produksi | |
| K16 | Rahasia tidak di repo (`.env`, token, `auth.json`) | `git log -p | grep -i token` kosong; secret scanning | |
| K17 | Dependensi tanpa kerentanan high/critical | `psp composer audit`, `psp npm audit` | |
| K18 | Lock & transaksi pada penomoran (registrasi, surat) dan transisi status; snapshot tautan dibuat dalam transaksi verifikasi | Test paralel nomor, `SnapshotTautanTest` | |
| K21 | Berkas sementara (`storage/app/tmp`) terhapus ≤ 24 jam; unduhan ekspor hanya oleh pemohon | `PenjadwalTest`, `EksporRekapTest` | |
| K19 | Horizon & log aktivitas hanya untuk peran berwenang | UAT S-03 | |
| K20 | Mass assignment: model memakai `$fillable` eksplisit; status tidak dapat diisi dari form | Tinjau model `Prestasi` | |

## 4. Checklist go-live

| No | Butir | Penanggung jawab | ✓ |
|---|---|---|---|
| G1 | Semua test & CI hijau pada tag rilis | Tim TI | |
| G2 | UAT seluruh peran lulus; temuan kritis ditutup | Pemilik produk | |
| G3 | Data prodi resmi (kode, kode PDDikti) dimuat; contoh dihapus | Admin fakultas | |
| G4 | Master mahasiswa aktif & dosen diimpor dari data resmi | Admin fakultas | |
| G5 | Kategori, tingkat, capaian & `kode_simkatmawa` diselaraskan dengan pedoman SIMKATMAWA terbaru (**perlu verifikasi**) | Admin fakultas | |
| G6 | Pemetaan kolom `config/simkatmawa.php` dicocokkan dengan templat isian terbaru, termasuk apakah SIMKATMAWA menerima tautan bukti atau mensyaratkan berkas (**perlu verifikasi**) | Admin fakultas | |
| G7 | Keputusan fakultas: tahap verifikasi prodi aktif/nonaktif; poin aktif/nonaktif; bobot poin via SK Dekan | Wakil Dekan | |
| G8 | Teks persetujuan publikasi & kebijakan privasi galeri ditinjau (UU PDP) | Pimpinan / bagian hukum | |
| G9 | Format nomor Surat Keterangan Prestasi & penandatangan ditetapkan | Tata usaha | |
| G10 | Pola email mahasiswa `@student.unsil.ac.id` & OAuth client Google dikonfirmasi UPT TIK (**perlu verifikasi**) | Tim TI | |
| G11 | SMTP kampus & token Fonnte produksi terpasang; uji kirim berhasil | Tim TI | |
| G12 | HTTPS, domain, DNS, sertifikat TLS | Tim TI | |
| G13 | Backup harian database berjalan & uji pulih pertama sukses | Tim TI | |
| G13b | Kebijakan tautan disosialisasikan: cara berbagi Drive, foto tanpa lokasi, anjuran Shared Drive fakultas untuk arsip bukti nasional/internasional | Bagian kemahasiswaan | |
| G13c | Kemampuan akun `@student.unsil.ac.id` berbagi ke domain `unsil.ac.id` dikonfirmasi (**perlu verifikasi** UPT TIK); daftar putih domain `config/berkas.php` disetujui | Tim TI | |
| G14 | Horizon, scheduler, queue container sehat; pemantauan `/api/health` | Tim TI | |
| G15 | Akun admin fakultas/prodi dibuat & MFA aktif; akun uji dihapus | Super admin | |
| G16 | `DemoSeeder` & `BobotPoinContohSeeder` tidak dijalankan di produksi | Tim TI | |
| G17 | Panduan pengguna tersedia di menu Bantuan; sosialisasi ke mahasiswa & dosen | Bagian kemahasiswaan | |
| G18 | Retensi log aktivitas ditetapkan (berkas sementara tetap ≤ 24 jam) | Pimpinan | |
| G19 | Tag `v1.0.0` dibuat, CHANGELOG lengkap, daftar "perlu verifikasi" yang masih terbuka dicatat | Tim TI | |

## Tambahan: subpath & panduan pengguna

| No | Butir | Hasil diharapkan | ✓ |
|---|---|---|---|
| SP-01 | Buka `https://supportfkip.unsil.ac.id/puspresma/` (staging/produksi) dan `http://localhost:8080/puspresma/` (gateway lokal) | Halaman, CSS/JS, Livewire, panel Filament, dan Horizon berjalan; tidak ada permintaan tanpa awalan `/puspresma` | |
| SP-02 | Login di `puspresma` lalu buka aplikasi lain di domain yang sama | Sesi tidak saling menimpa (cookie `puspresma_session`, path `/puspresma`) | |
| SP-03 | Pindai QR / buka tautan yang dibangkitkan sistem | URL memuat `https://supportfkip.unsil.ac.id/puspresma/…` | |
| PG-01 | Buka `/puspresma/panduan/` dari landing page aplikasi dan dari landing page supportfkip | Daftar peran tampil; setiap peran punya halaman HTML dengan tangkapan layar terbaru | |
| PG-02 | Ikuti panduan setiap peran memakai akun UAT | Langkah & tangkapan layar sesuai tampilan sistem | |


# Panduan Super Admin

Super-admin mengelola pengguna dan sistem. Super-admin **tidak memverifikasi prestasi** (pemisahan tugas).

## 1. Pengguna dan peran
1. Menu **Sistem → Pengguna**: buat akun, tetapkan peran (`super-admin`, `admin-fakultas`, `admin-prodi`, `pimpinan`, `dosen`, `mahasiswa`) dan **program studi** (wajib untuk admin prodi: menentukan prestasi yang dapat dilihat dan diverifikasi).
2. Akun mahasiswa/dosen dibuat dari master; nonaktifkan, bukan hapus, akun yang tidak dipakai.
3. **Log Aktivitas** memuat jejak audit (pembukaan tautan, transisi status, perubahan data, kata sandi diganti, masuk sebagai).
4. Akun super-admin awal dibuat oleh seeder (`fkip@unsil.ac.id`). **Segera ganti kata sandi awalnya** lewat Profil.

## 1a. Impor pengguna massal (salin-tempel)
1. Menu **Sistem → Impor Pengguna**.
2. Salin sel dari Excel/Google Sheets lalu tempel (CSV atau titik koma juga diterima). Baris judul opsional. Urutan kolom: `nama, surel, peran, kode prodi, NPM, NIDN, NIP, nomor WA, sandi`; hanya nama, surel, dan peran yang wajib. Beberapa peran dipisah `|`.
3. Tekan **Periksa dahulu** untuk melihat status tiap baris (akun baru, perbarui, atau galat dengan alasannya). Tidak ada yang disimpan pada tahap ini.
4. Isi **sandi awal** (opsional) dan pilih apakah pengguna **wajib mengganti kata sandi saat masuk pertama**. Tanpa sandi, pengguna baru menerima tautan atur kata sandi lewat surel.
5. Tekan **Impor sekarang**. Baris yang gagal tidak membatalkan baris lain; hasilnya ditampilkan beserta alasannya. Peran `super-admin` tidak dapat diberikan lewat impor. Maksimal 500 baris per impor.

## 1b. Kata sandi, wajib ganti sandi, dan sesi
- Pengguna dapat mengubah profil dan kata sandinya sendiri (menu Profil). Saat kata sandi diganti, semua sesi di perangkat lain otomatis keluar.
- Untuk mewajibkan penggantian: buka **Pengguna → Edit** lalu aktifkan **Wajib ganti kata sandi**, atau pilih banyak akun di tabel dan jalankan aksi **Wajibkan ganti kata sandi**. Pengguna diarahkan ke halaman profil pada akses berikutnya dan baru dapat melanjutkan setelah menggantinya. Cabut kewajiban dengan aksi **Cabut kewajiban ganti kata sandi**.
- Mengganti kata sandi pengguna dari formulir pengguna juga mengeluarkan sesi aktif pengguna itu.

## 1c. Masuk sebagai pengguna lain (keadaan darurat)
Hanya super-admin, untuk membantu pengguna yang terkunci atau memeriksa masalah yang tidak dapat direproduksi.
1. Di **Pengguna**, pilih **Masuk sebagai** pada baris pengguna (atau di halaman detailnya), lalu konfirmasi.
2. Banner merah **Mode darurat** tampil di setiap halaman selama sesi. Tekan **Kembali ke akun saya** untuk mengakhiri.
3. Awal dan akhir sesi dicatat di Log Aktivitas, dan setiap tindakan selama sesi membawa `impersonator_id` atas nama Anda.
4. Tidak berlaku untuk diri sendiri atau akun nonaktif, tidak dapat bersarang, dan kata sandi tidak dapat diganti selama sesi ini.

## 2. MFA
1. Autentikasi dua langkah (aplikasi authenticator) saat ini **dimatikan** (`MFA_AKTIF=false` di `.env`) agar akun dapat langsung dipakai. Menyalakannya kembali: `MFA_AKTIF=true`, lalu jalankan ulang aplikasi; super-admin dan admin-fakultas lalu wajib memasangnya pada login berikutnya.
2. Bila diaktifkan, simpan kode pemulihan di tempat aman dan jangan membagikan rahasia MFA. **Reset MFA** tersedia di tabel pengguna.

## 3. Horizon dan antrean
- Dasbor Horizon (`/horizon`) menampilkan antrean `notifikasi`, `default`, `berkas`, `impor`, `ekspor`. Setelah mengubah kode job, restart layanan Horizon.
- Job gagal dipangkas otomatis setelah 30 hari. `/api/health` menampilkan status DB, Redis, dan ukuran antrean.
- Ringkasan antrean mingguan dikirim ke surel admin.

## 4. Backup
1. Basis data dicadangkan otomatis harian 01:00 (terenkripsi), dibersihkan 01:30, dipantau 09:00; kegagalan dikirim ke surel super-admin.
2. Password arsip disimpan terpisah dari server. Prosedur pulih dan formulir uji semester ada di `docs/PEMULIHAN.md`.
3. Bukti prestasi ada di Google Drive mahasiswa; fakultas dianjurkan menyalin bukti nasional/internasional ke Shared Drive fakultas.

## Tanya-jawab
- *Notifikasi backup tidak masuk.* Pastikan akun super-admin memiliki surel dan SMTP terisi.

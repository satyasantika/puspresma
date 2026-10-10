# Panduan Super Admin

Super-admin mengelola pengguna dan sistem. Super-admin **tidak memverifikasi prestasi** (pemisahan tugas).

## 1. Pengguna dan peran
1. Menu **Sistem → Pengguna**: buat akun, tetapkan peran (`super-admin`, `admin-fakultas`, `admin-prodi`, `pimpinan`, `dosen`, `mahasiswa`) dan prodi untuk admin prodi.
2. Akun mahasiswa/dosen dibuat dari master; nonaktifkan, bukan hapus, akun yang tidak dipakai.
3. **Log Aktivitas** memuat jejak audit (pembukaan tautan, transisi status, perubahan data).

## 2. MFA
1. Aktifkan autentikasi dua langkah untuk akun admin dari profil pengguna.
2. Simpan kode pemulihan di tempat aman. Jangan membagikan rahasia MFA.

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

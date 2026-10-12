# Panduan Admin Fakultas

Admin fakultas memegang verifikasi akhir dan pengelolaan data kunci sistem.

## 1. Verifikasi dengan membuka tautan
1. **Prestasi** → antrean. Buka **Periksa**.
2. Buka setiap tautan bukti lewat tombol tautan (wajib; tercatat di log), lalu centang bahwa tautan sudah dibuka.
3. Bila ada konfirmasi pembimbing *menunggu*, centang dan beri catatan; bila *disangkal*, verifikasi diblokir.
4. Klik **Verifikasi**. Sistem menyimpan snapshot URL (tidak dapat diubah) dan menghitung poin bila aktif.
5. **Perlu perbaikan**, **Tolak**, dan **Buka verifikasi** wajib catatan ≥ 10 karakter. Ditolak bersifat final.

## 2. Duplikat dan ganti tautan
- **Kandidat Duplikat**: tinjau pasangan dan tandai duplikat/bukan duplikat; kandidat belum ditinjau memblokir verifikasi.
- **Ganti tautan** setelah verifikasi hanya oleh admin fakultas dengan catatan; URL lama → baru dicatat.

## 3. Impor dan master
- **Impor Mahasiswa** / **Impor Dosen**: unggah Excel/CSV (berkas diproses lalu dihapus); data di-upsert berdasarkan NPM/NIDN. Periksa riwayat impor.
- Menu **Master**: Program Studi, Jenis Kegiatan, Kategori Lomba, Tingkat, Capaian, Mahasiswa, Dosen.

## 4. Katalog, galeri, SIMKATMAWA, surat
- **Katalog Kompetisi**: tambah/ubah lomba; yang lewat tenggat diarsipkan otomatis.
- **Moderasi Galeri**: sembunyikan prestasi dari galeri publik bila perlu.
- **Ekspor SIMKATMAWA**: unduh kolom terpetakan dan tandai *dilaporkan* (hanya prestasi terverifikasi).
- **Surat Keterangan**: terbitkan/cetak surat ber-QR (nomor berurutan, PDF di-stream).

## 5. Pengaturan dan bobot poin
- **Pengaturan Sistem**: ambang (jumlah negara/PT), tahap prodi, mode poin, dan lain-lain. Jangan menyimpan ambang di kode.
- **Matriks Bobot Poin**: bobot jenis × tingkat × capaian berlaku per tanggal; perubahan tidak mengubah snapshot lama.

## Profil dan kata sandi
1. Buka menu profil (ikon pengguna di pojok kanan atas, lalu **Profil**).
2. Ubah nama dan nomor WhatsApp. Surel tertaut ke data induk kampus dan tidak dapat diubah di sini.
3. Untuk mengganti kata sandi, isi kata sandi saat ini lalu kata sandi baru (minimal 12 karakter, memuat huruf dan angka).
4. Setelah kata sandi diganti, **semua perangkat lain otomatis keluar**; sesi Anda saat ini tetap masuk.
5. Bila administrator mewajibkan penggantian, Anda diarahkan ke halaman ini lebih dulu dan menu lain terbuka setelah kata sandi diganti.
6. Lupa kata sandi? Pilih **Lupa kata sandi** di halaman masuk; tautan atur ulang dikirim ke surel akun Anda.

## Tanya-jawab
- *Tombol verifikasi nonaktif.* Cek duplikat belum ditinjau, tautan belum dibuka, atau konfirmasi pembimbing disangkal.
- *Super-admin tidak bisa memverifikasi.* Itu disengaja (pemisahan tugas).

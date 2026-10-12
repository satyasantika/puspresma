<?php

namespace App\Support;

/** Isi halaman galat HTTP: judul, penjelasan, saran, dan tombol utama. Dipakai views/errors/*. */
class HalamanGalat
{
    /** @var array<int, array{judul: string, pesan: string, saran: list<string>, aksi?: 'masuk'|'muat-ulang'|'beranda'}> */
    private const DAFTAR = [
        400 => ['judul' => 'Permintaan tidak dapat dipahami', 'pesan' => 'Peramban mengirim permintaan yang tidak lengkap atau rusak.', 'saran' => ['Muat ulang halaman, lalu ulangi langkah Anda.', 'Bila berulang, hapus cookie situs ini dan coba lagi.']],
        401 => ['judul' => 'Anda perlu masuk', 'pesan' => 'Halaman ini hanya untuk pengguna yang sudah masuk.', 'saran' => ['Masuk dengan akun kampus Anda.', 'Sesi dapat berakhir otomatis setelah lama tidak aktif.'], 'aksi' => 'masuk'],
        402 => ['judul' => 'Layanan belum tersedia', 'pesan' => 'Permintaan ini tidak dapat dipenuhi saat ini.', 'saran' => ['Hubungi admin fakultas bila Anda membutuhkannya.']],
        403 => ['judul' => 'Akses ditolak', 'pesan' => 'Akun Anda tidak memiliki izin untuk membuka halaman atau melakukan tindakan ini.', 'saran' => ['Pastikan Anda masuk dengan akun yang benar.', 'Prestasi dan data prodi lain hanya dapat dilihat oleh pihak yang berwenang.', 'Bila menurut Anda ini keliru, hubungi admin prodi atau admin fakultas.']],
        404 => ['judul' => 'Halaman tidak ditemukan', 'pesan' => 'Alamat yang Anda tuju tidak ada, sudah dipindahkan, atau datanya telah dihapus.', 'saran' => ['Periksa kembali penulisan alamat.', 'Prestasi yang belum terverifikasi atau belum disetujui tampil publik tidak muncul di galeri.', 'Mulai dari beranda atau galeri prestasi.']],
        405 => ['judul' => 'Tindakan tidak diizinkan', 'pesan' => 'Halaman ini tidak menerima cara akses yang Anda gunakan.', 'saran' => ['Buka halaman lewat tautan atau menu, bukan dengan mengetik alamat untuk mengirim data.']],
        408 => ['judul' => 'Waktu permintaan habis', 'pesan' => 'Server menunggu terlalu lama sebelum permintaan Anda selesai dikirim.', 'saran' => ['Periksa koneksi internet Anda, lalu coba lagi.'], 'aksi' => 'muat-ulang'],
        409 => ['judul' => 'Data sedang berubah', 'pesan' => 'Data yang Anda buka baru saja diubah pihak lain atau sedang diproses.', 'saran' => ['Muat ulang halaman untuk melihat keadaan terbaru, lalu ulangi.'], 'aksi' => 'muat-ulang'],
        410 => ['judul' => 'Halaman sudah tidak tersedia', 'pesan' => 'Konten ini telah dihapus secara permanen.', 'saran' => ['Lihat daftar terbaru di galeri atau katalog kompetisi.']],
        413 => ['judul' => 'Data terlalu besar', 'pesan' => 'Berkas atau data yang Anda kirim melebihi batas yang diizinkan.', 'saran' => ['Impor Excel/CSV dibatasi 10 MB: pecah menjadi beberapa berkas.', 'Bukti prestasi tidak diunggah; cukup tempelkan tautan Google Drive.']],
        414 => ['judul' => 'Alamat terlalu panjang', 'pesan' => 'Alamat yang dibuka melebihi panjang yang dapat diproses.', 'saran' => ['Gunakan menu atau filter di halaman, bukan menempel alamat yang sangat panjang.']],
        419 => ['judul' => 'Sesi telah kedaluwarsa', 'pesan' => 'Halaman terlalu lama terbuka sehingga keamanannya kedaluwarsa. Data Anda belum tersimpan.', 'saran' => ['Muat ulang halaman, lalu kirim kembali.', 'Bila diminta, masuk lagi.'], 'aksi' => 'muat-ulang'],
        422 => ['judul' => 'Data tidak dapat diproses', 'pesan' => 'Isian yang dikirim tidak memenuhi aturan.', 'saran' => ['Kembali ke formulir, periksa pesan kesalahan, lalu kirim ulang.']],
        423 => ['judul' => 'Sedang dikunci', 'pesan' => 'Data ini sedang diproses oleh pengguna atau proses lain.', 'saran' => ['Tunggu beberapa saat, lalu muat ulang.'], 'aksi' => 'muat-ulang'],
        429 => ['judul' => 'Terlalu banyak permintaan', 'pesan' => 'Anda mengakses terlalu sering dalam waktu singkat. Ini pengaman agar layanan tetap lancar bagi semua orang.', 'saran' => ['Tunggu sebentar, lalu coba lagi.', 'Untuk masuk, batas percobaan kata sandi adalah 5 kali per menit.'], 'aksi' => 'muat-ulang'],
        500 => ['judul' => 'Terjadi kesalahan pada sistem', 'pesan' => 'Maaf, ada gangguan di sisi kami. Data Anda aman dan tim telah menerima catatan kesalahan ini.', 'saran' => ['Muat ulang halaman beberapa saat lagi.', 'Bila Anda sedang mengajukan prestasi, periksa dulu daftar Prestasi Saya sebelum mengirim ulang agar tidak ganda.', 'Bila berulang, laporkan ke admin fakultas beserta waktu kejadian.'], 'aksi' => 'muat-ulang'],
        501 => ['judul' => 'Fitur belum tersedia', 'pesan' => 'Layanan yang diminta belum didukung.', 'saran' => ['Kembali ke beranda.']],
        502 => ['judul' => 'Gerbang layanan bermasalah', 'pesan' => 'Server aplikasi tidak menjawab dengan benar. Biasanya sementara, misalnya saat pembaruan.', 'saran' => ['Tunggu satu-dua menit, lalu muat ulang.'], 'aksi' => 'muat-ulang'],
        503 => ['judul' => 'Sedang dalam pemeliharaan', 'pesan' => 'Puspresma sedang diperbarui atau dipulihkan. Layanan akan kembali sesaat lagi.', 'saran' => ['Data yang sudah tersimpan tidak terpengaruh.', 'Halaman ini memuat ulang sendiri secara berkala.'], 'aksi' => 'muat-ulang'],
        504 => ['judul' => 'Server terlalu lama menjawab', 'pesan' => 'Proses memakan waktu lebih lama dari batas. Bila Anda mengekspor data besar, hasilnya dikirim lewat antrean.', 'saran' => ['Coba lagi dengan filter yang lebih sempit.', 'Ekspor besar dapat diunduh dari halaman Berkas Ekspor setelah selesai.'], 'aksi' => 'muat-ulang'],
    ];

    /** @return array{kode: int, judul: string, pesan: string, saran: list<string>, aksi: string} */
    public static function untuk(int $kode): array
    {
        $isi = self::DAFTAR[$kode] ?? null;

        $isi ??= $kode >= 500
            ? ['judul' => 'Layanan bermasalah', 'pesan' => 'Terjadi gangguan di sisi server.', 'saran' => ['Muat ulang halaman beberapa saat lagi.'], 'aksi' => 'muat-ulang']
            : ['judul' => 'Permintaan tidak dapat dilayani', 'pesan' => 'Halaman yang Anda minta tidak dapat ditampilkan.', 'saran' => ['Kembali ke beranda dan coba lagi.']];

        return $isi + ['kode' => $kode, 'aksi' => 'beranda'];
    }

    /** @return list<int> */
    public static function kodeTerdaftar(): array
    {
        return array_keys(self::DAFTAR);
    }
}

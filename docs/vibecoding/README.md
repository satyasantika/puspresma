# Bahan Vibecoding — Puspresma FKIP Unsil

Paket ini berisi semua bahan untuk membangun **Puspresma (Pusat Prestasi Mahasiswa) FKIP Universitas Siliwangi** dengan agen AI (mis. Claude Code), langkah demi langkah, dengan commit Git standar di setiap langkah. Stack: Laravel 13, PHP 8.4, MySQL 8.4, Redis 7, Filament 5, Livewire 4.

## Tujuan paket

- Memberi dosen/staf FKIP satu jalur kerja yang jelas: dari repositori kosong sampai rilis `v1.0.0`.
- Menjaga agen AI tetap pada cakupan: setiap prompt menyebut tabel, kolom, kelas, route, aturan bisnis (BR-xx), dan test yang harus dibuat.
- Menghasilkan riwayat Git yang rapi (Conventional Commits, satu fase satu PR, tag SemVer).

## Isi paket

| Berkas | Isi | Kapan dibaca |
|---|---|---|
| `01-PRD.md` | Masalah, dasar regulasi (dengan tanda "perlu verifikasi"), peran & matriks izin, modul, user story, aturan bisnis BR-01..BR-27, diagram status, kebutuhan non-fungsional, laporan, di luar cakupan | Pertama, oleh semua |
| `02-ARSITEKTUR.md` | Paket Composer/NPM, pemakaian Redis (cache, job, lock, rate limit), struktur `app/`, panel & Resource Filament, route publik, penjadwal, notifikasi, strategi berkas berbasis tautan, diagram komponen | Sebelum F1 |
| `03-SKEMA-DATABASE.md` | ERD, enum, kolom per tabel, indeks, seeder awal | Sebelum F3 |
| `04-PROMPT-BERTAHAP.md` | **Inti**: 10 fase, 59 langkah, prompt siap salin, kriteria selesai, perintah commit | Saat membangun |
| `05-UJI-PENERIMAAN.md` | Skenario UAT per peran, daftar test Pest, checklist keamanan, checklist go-live | F9 dan saat UAT |
| `CLAUDE.md` | Konteks ringkas untuk agen AI (disalin ke root repo) | Dibaca agen tiap sesi |
| `STANDAR-TEKNIS.md` | Standar teknis bersama seluruh sistem FKIP | Rujukan |
| `STANDAR-GIT.md` | Standar commit, branch, tag, hook | Rujukan |

## Kebijakan berkas

Sesuai STANDAR-TEKNIS §1a (keputusan 6 Oktober 2026), Puspresma **tidak menerima unggahan berkas** karena kapasitas storage server terbatas. Sertifikat/piagam, foto penyerahan, surat tugas, dan poster kompetisi diisi sebagai **tautan** Google Drive (atau penyedia lain dalam daftar putih). Verifikator membuka tautan saat verifikasi dan sistem menyimpan snapshot URL saat prestasi terverifikasi. Galeri publik menampilkan foto langsung dari Google (`lh3.googleusercontent.com`) hanya bila mahasiswa menyetujui publikasi dan berkas dibagikan publik. Satu-satunya input berkas adalah impor Excel master mahasiswa/dosen, yang diproses lalu dihapus.

## Urutan pakai

1. Baca `01-PRD.md` → `05-UJI-PENERIMAAN.md` (minimal 01, 02, 03) dan sepakati keputusan terbuka: tahap verifikasi prodi, poin aktif/tidak, butir "perlu verifikasi".
2. Lengkapi acuan regulasi di folder sistem (lihat bagian di bawah).
3. Buat repositori `puspresma`, jalankan langkah **F0.1** — langkah ini menyalin `CLAUDE.md` ke root repo dan bahan ini ke `docs/vibecoding/`.
4. Jalankan `04-PROMPT-BERTAHAP.md` **langkah demi langkah**: tempel prompt ke agen, periksa "Selesai bila", tinjau `git diff`, jalankan blok Commit.
5. Di akhir setiap fase: push branch, PR, CI hijau, squash-merge, tag.
6. Fase F9: UAT memakai `05-UJI-PENERIMAAN.md`, lalu rilis `v1.0.0`.

## Fase dan tag versi

| Fase | Judul | Branch | Tag |
|---|---|---|---|
| F0 | Persiapan repositori | `main` | — |
| F1 | Fondasi Laravel (Docker FKIP, Redis, Horizon, Pest, Larastan, Boost, CI) | `main` | `v0.1.0` |
| F2 | Autentikasi, peran & audit (Filament 5, spatie/permission, activitylog, MFA) | `feat/f2-auth-peran` | `v0.2.0` |
| F3 | Data master, impor Excel, pengaturan, bobot poin, fondasi tautan berkas, login Google | `feat/f3-master-data` | `v0.3.0` |
| F4 | Pengajuan prestasi, tim lintas prodi, tautan bukti | `feat/f4-pengajuan-prestasi` | `v0.4.0` |
| F5 | Verifikasi (buka tautan, snapshot URL), deteksi duplikat, konfirmasi pembimbing, poin | `feat/f5-verifikasi` | `v0.5.0` |
| F6 | Katalog kompetisi & galeri publik (persetujuan UU PDP) | `feat/f6-katalog-galeri` | `v0.6.0` |
| F7 | Notifikasi (mail, database, WhatsApp) & penjadwal | `feat/f7-notifikasi-jadwal` | `v0.7.0` |
| F8 | Dasbor, laporan akreditasi/IKU, ekspor SIMKATMAWA, surat ber-QR | `feat/f8-laporan-ekspor` | `v0.8.0` |
| F9 | Pengerasan (keamanan, kinerja, Docker, backup database) & rilis | `feat/f9-pengerasan` | `v1.0.0` |

## Prasyarat

| Kebutuhan | Versi / catatan |
|---|---|
| PHP | 8.4 (minimal 8.3) — di dalam image `puspresma-php` (Docker FKIP, STANDAR-TEKNIS §2) |
| Composer | 2.x — di dalam image `puspresma-php`, tidak perlu di host |
| Node.js | 22 LTS (Vite, Tailwind 4) — di dalam image `puspresma-php`, tidak perlu di host |
| Docker | Docker Desktop dengan **WSL2** (Ubuntu) di Windows; kerjakan di filesystem WSL, bukan `C:\` |
| Git | 2.40+; akun GitHub/GitLab untuk remote & CI; `gh` CLI opsional untuk PR |
| Agen AI | Claude Code (atau sejenis) yang dapat membaca `CLAUDE.md` dan memakai MCP Laravel Boost |
| Akses | Kredensial OAuth Google kampus (opsional), SMTP kampus, token Fonnte (opsional), data resmi prodi/mahasiswa/dosen, akun Google Drive uji (untuk tautan bukti) |

## Acuan regulasi yang perlu ditambahkan ke folder sistem

Folder sistem saat ini hanya memuat Permendikbudristek No. 46 Tahun 2023 (PPKSP), yang relevansinya terbatas bagi Puspresma (lihat `01-PRD.md` §2). Sebelum F3 dan F8, mohon tambahkan versi terbaru dari:

1. Pedoman SIMKATMAWA / Pedoman Pemeringkatan Kemahasiswaan (Puspresnas, Kemendiktisaintek): menentukan kategori, tingkat, capaian, dan kolom isian pelaporan (termasuk apakah bukti boleh berupa tautan).
2. Pedoman Pemilihan Mahasiswa Berprestasi (Pilmapres).
3. Ketentuan IKU PTN terkait mahasiswa berprestasi yang berlaku.
4. Ketentuan SKPI (Surat Keterangan Pendamping Ijazah).
5. Instrumen akreditasi LAMDIK (tabel prestasi mahasiswa).
6. SK Dekan/pedoman internal tentang poin prestasi dan beasiswa berbasis prestasi (bila ada).

Semua butir bertanda **(perlu verifikasi)** di dokumen ini dibuat konfigurable (master data, `pengaturan`, `config/simkatmawa.php`) sehingga dapat disesuaikan tanpa mengubah kode.

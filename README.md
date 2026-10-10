# Puspresma FKIP Unsil

[![CI](https://github.com/satyasantika/puspresma/actions/workflows/ci.yml/badge.svg)](https://github.com/satyasantika/puspresma/actions/workflows/ci.yml)

Puspresma (Pusat Prestasi Mahasiswa) FKIP Universitas Siliwangi mencatat prestasi akademik dan non-akademik mahasiswa
(kompetisi dan rekognisi; tingkat lokal, wilayah, nasional, internasional) sebagai bukti dukung akreditasi, laporan
kinerja/IKU, bahan ekspor SIMKATMAWA, dan seleksi beasiswa. Bukti prestasi berupa tautan (tanpa unggah berkas ke server).

Stack: Laravel 13, PHP 8.5, MariaDB, Redis 7, Filament 5, Livewire 4, Tailwind 4.

## Dokumentasi

Lihat `docs/`: keputusan teknis (`KEPUTUSAN.md`) dan deploy (`DEPLOY.md`; CI di `.github/workflows/ci.yml`, deploy otomatis di `deploy.yml`).

## Menjalankan lokal

Aplikasi memakai container yang dikelola compose pusat `~/code/docker-compose.yml` (`puspresma-php` PHP 8.5, `puspresma-nginx`, jaringan `laranet`), **MariaDB** native di host (DB `db_puspresma`, `db_puspresma_testing`), dan Redis bersama (`redis`, DB 40/41/42). Tidak ada container milik repo ini (tanpa Sail, tanpa MySQL/Redis/Mailpit sendiri).

```bash
alias psp='docker exec -w /var/www/html puspresma-php'
cp .env.example .env && psp php artisan key:generate   # isi DB_PASSWORD (dan REDIS_PASSWORD) di .env
psp composer install
psp php artisan migrate --seed
git config core.hooksPath .githooks                     # aktifkan hook commit-msg dan pre-commit
```

Aplikasi: http://localhost:8032 (port dari compose pusat). Perintah PHP/Composer selalu lewat `psp`; `npm install && npm run dev` dijalankan dari host (container belum berisi Node). Git dijalankan di host.

Pemeliharaan: bila container `puspresma-php` dibuat ulang dan nginx menjawab 502, jalankan `docker exec puspresma-nginx nginx -s reload`.

Antrean dan penjadwal berjalan sebagai service compose pusat `puspresma-horizon` (`php artisan horizon`) dan `puspresma-scheduler` (`schedule:work`). Setelah mengubah kode job: `cd ~/code && docker compose restart puspresma-horizon`. Dasbor Horizon: http://localhost:8032/horizon (hanya lingkungan local sampai F2.2).

## Penjadwal

Service `puspresma-scheduler` menjalankan `php artisan schedule:work` (lock Redis, `onOneServer` + `withoutOverlapping`). Jadwal: `horizon:snapshot` tiap 5 menit; `puspresma:arsipkan-kompetisi` 00:30; `puspresma:bersihkan-tmp` tiap jam; `puspresma:periksa-tautan` Minggu 02:00; pengingat tenggat kompetisi 07:00; `puspresma:ringkasan-antrean` Senin–Jumat 07:30; `puspresma:pengingat-pembimbing` Senin 08:00; `activitylog:clean` dan `queue:prune-failed --hours=720` tiap tanggal 1. Lihat daftar lengkap dengan `psp php artisan schedule:list`, jalankan satu tugas dengan `psp php artisan schedule:test`, dan pantau log penjadwal dengan `docker compose logs -f puspresma-scheduler`.

## Subpath produksi `/puspresma`

Produksi: `https://supportfkip.unsil.ac.id/puspresma` (reverse proxy memotong awalan). Atur di `.env`: `APP_URL` dan `ASSET_URL` = URL tersebut, `SESSION_PATH=/puspresma`, `SESSION_COOKIE=puspresma_session`, `SESSION_SECURE_COOKIE=true`, `TRUSTED_PROXIES=<ip proxy>`. Bila `APP_URL` berpath, `AppServiceProvider` memaksa root URL (dan skema https); semua URL harus lewat `route()`/`url()`/`asset()` (diuji `tests/Arch/SubpathTest.php` dan `tests/Feature/SubpathTest.php`). Gateway lokal `:8080` tidak tersedia pada mesin ini; akses lokal langsung di `http://localhost:8032`, subpath diuji lewat konfigurasi override di test.

Build aset dengan awalan (dijalankan di host; `public/build/` tidak di-commit): `ASSET_URL=https://supportfkip.unsil.ac.id/puspresma npm run build` sehingga `laravel-vite-plugin` menulis URL aset (termasuk `url()` di CSS) berawalan `/puspresma/build/`.

```nginx
location = /puspresma { return 301 /puspresma/; }
location /puspresma/ {
    proxy_pass http://127.0.0.1:8032/;          # garis miring akhir = awalan dipotong
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Prefix /puspresma;
    client_max_body_size 20m;
}
```

QR dan tautan tercetak memuat awalan `/puspresma`: tetapkan awalan final sebelum mencetak, jangan diubah setelah produksi.

## Pengujian

Test memakai Pest 4 dan basis data MariaDB host terpisah `db_puspresma_testing` (cache `array`, antrean `sync`, sesi `array`; tidak menyentuh Redis). Buat sekali di MariaDB host (ganti `<rahasia>` dan sesuaikan host pengguna dengan subnet Docker):

```sql
CREATE DATABASE db_puspresma CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE db_puspresma_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `db_puspresma%`.* TO 'app'@'172.%';
```

Perintah: `psp php artisan test` (atau `psp ./vendor/bin/pest`), `psp ./vendor/bin/phpstan analyse --memory-limit=1G` (Larastan level 6), `psp ./vendor/bin/pint --test`. Pintasan Composer: `composer test`, `composer lint`, `composer stan`, `composer cek`.

Laravel Boost terpasang (`boost.json`, `.mcp.json` lokal memanggil `puspresma-php`); pedoman Boost berada di akhir `CLAUDE.md` lokal.

Gerbang kualitas sebelum commit: `psp ./vendor/bin/pint --test && psp ./vendor/bin/phpstan analyse --memory-limit=1G && psp php artisan test`.

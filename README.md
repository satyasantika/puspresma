# Puspresma FKIP Unsil

Puspresma (Pusat Prestasi Mahasiswa) FKIP Universitas Siliwangi mencatat prestasi akademik dan non-akademik mahasiswa
(kompetisi dan rekognisi; tingkat lokal, wilayah, nasional, internasional) sebagai bukti dukung akreditasi, laporan
kinerja/IKU, bahan ekspor SIMKATMAWA, dan seleksi beasiswa. Bukti prestasi berupa tautan (tanpa unggah berkas ke server).

Stack: Laravel 13, PHP 8.5, MariaDB, Redis 7, Filament 5, Livewire 4, Tailwind 4.

## Dokumentasi

Lihat `docs/` (keputusan teknis di `docs/KEPUTUSAN.md`).

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

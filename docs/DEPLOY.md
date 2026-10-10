# Deploy produksi — Puspresma FKIP Unsil

Pola docker-apps: aplikasi berjalan di belakang reverse proxy fakultas pada `https://supportfkip.unsil.ac.id/puspresma`
(loopback `127.0.0.1:8032`, awalan dipotong). Berkas: `Dockerfile` (target `app` dan `web`), `docker-compose.prod.yml`,
`.env.production.example`, `docker/` (php, nginx, backup).

## Rantai otomatis (GitHub Actions)

- `.github/workflows/ci.yml`: pada push ke `main` dan setiap pull request menjalankan Pint, Larastan level 6, dan Pest
  (MariaDB 10.11 dan Redis 7 sebagai service, PHP 8.5, Node 22).
- `.github/workflows/deploy.yml`: pada push ke `main` masuk ke server lewat `appleboy/ssh-action` dan menjalankan
  `~/docker-apps/deploy.sh puspresma`. Secret repositori yang harus diisi manusia: `SERVER_HOST`, `SERVER_USER`, `SSH_PRIVATE_KEY`.
  Skrip `deploy.sh` (di server, bukan di repo ini) harus mengenali nama `puspresma`: tarik kode, build citra, `up -d`,
  `migrate --force`, dan `optimize`.
- Proteksi branch `main` (wajib PR dan CI hijau) diatur manusia di pengaturan repositori.

## Langkah pertama kali (manual di server)

```bash
git clone <repo> /opt/puspresma && cd /opt/puspresma
cp .env.production.example .env && chmod 600 .env     # isi DB_PASSWORD, REDIS_PASSWORD, SMTP, TRUSTED_PROXIES
APP_VERSION=$(git describe --tags --always) docker compose -f docker-compose.prod.yml build   # ASSET_URL wajib terisi; APP_VERSION tampil di footer dan /api/health
docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate --show   # salin ke APP_KEY dan SIMPAN cadangannya
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan optimize
```

Basis data: MariaDB 10.11 native di host (`db_puspresma`, pengguna khusus). Blok reverse proxy ada di `README.md`.
Cadangan harian dan uji pulih: `docker/backup/backup.sh` dan `uji-pulih.sh` (profil compose `backup`, dipanggil cron host).

## Migrasi, kesehatan, dan volume

- Migrasi otomatis hanya bila `RUN_MIGRATIONS=true` (container `app`); default `false`. Entrypoint menunggu DB, lalu menjalankan `optimize`, `filament:optimize`, dan `icons:cache` (`storage:link` tidak diperlukan: tidak ada berkas publik di server).
- Healthcheck nginx memakai `/api/health` (`status: ok`). Redis dan MySQL tidak membuka port keluar.
- Volume `storage` hanya memuat sesi/log/cache dan `storage/app/tmp` (berkas sementara ≤ 24 jam); tidak ada berkas pengguna yang perlu dipertahankan.
- Reverse proxy: blok `location /puspresma/` ada di `README.md`; `TRUSTED_PROXIES` diisi IP proxy (bukan `*`).


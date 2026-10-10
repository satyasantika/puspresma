# Deploy produksi — Puspresma FKIP Unsil

Pola docker-apps: aplikasi berjalan di belakang reverse proxy fakultas pada `https://supportfkip.unsil.ac.id/puspresma`
(loopback `127.0.0.1:8032`, awalan dipotong). Berkas: `Dockerfile` (target `app` dan `web`), `docker-compose.prod.yml`,
`.env.production.example`, `docker/` (php, nginx, backup).

## Rantai otomatis (GitHub Actions)

- `.github/workflows/ci.yml`: pada push ke `main` dan setiap pull request menjalankan Pint, Larastan level 6, dan Pest
  (MariaDB 10.11 dan Redis 7 sebagai service, PHP 8.5, Node 22).
- Job `deploy` di `ci.yml` (`needs: uji`): hanya pada push ke `main` **setelah** Pint, Larastan, dan Pest hijau. Ia masuk ke server
  lewat `appleboy/ssh-action`, lalu `git fetch` + `git reset --hard origin/main` di direktori aplikasi dan menjalankan
  `bash scripts/deploy.sh`. Skrip rilis ikut ter-versioning di repo (tidak ada lagi `~/docker-apps/deploy.sh` di luar repo).
  `concurrency: deploy-produksi` mencegah dua rilis saling menyalip.
- Secret repositori (diisi manusia): `SERVER_HOST`, `SERVER_USER`, `SSH_PRIVATE_KEY`; opsional `DEPLOY_PATH` (bawaan `~/docker-apps/puspresma`).
  Pengguna server harus ada di grup `docker`, dan kunci publik pasangannya ada di `authorized_keys`.
- Proteksi branch `main` (wajib PR dan CI hijau) diatur manusia di pengaturan repositori.

## Langkah pertama kali (manual di server)

```bash
git clone <repo> ~/docker-apps/puspresma && cd ~/docker-apps/puspresma
cp .env.production.example .env && chmod 600 .env     # isi DB_PASSWORD, REDIS_PASSWORD, SMTP, TRUSTED_PROXIES
APP_VERSION=$(git describe --tags --always) docker compose -f docker-compose.prod.yml build   # ASSET_URL wajib terisi; APP_VERSION tampil di footer dan /api/health
docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate --show   # salin ke APP_KEY dan SIMPAN cadangannya
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan optimize
```

Basis data: MariaDB 10.11 native di host (`db_puspresma`, pengguna khusus). Blok reverse proxy ada di `README.md`.
Cadangan harian: `spatie/laravel-backup` lewat scheduler (`backup:run --only-db` 01:00; terenkripsi) — lihat `docs/PEMULIHAN.md`. `docker/backup/backup.sh` (mysqldump) tetap tersedia sebagai cadangan kedua di host.

## Migrasi, kesehatan, dan volume

- Migrasi otomatis hanya bila `RUN_MIGRATIONS=true` (container `app`); default `false`. Entrypoint menunggu DB, lalu menjalankan `optimize`, `filament:optimize`, dan `icons:cache` (`storage:link` tidak diperlukan: tidak ada berkas publik di server).
- Healthcheck nginx memakai `/api/health` (`status: ok`). Redis dan MySQL tidak membuka port keluar.
- Volume `storage` hanya memuat sesi/log/cache dan `storage/app/tmp` (berkas sementara ≤ 24 jam); tidak ada berkas pengguna yang perlu dipertahankan.
- Reverse proxy: blok `location /puspresma/` ada di `README.md`; `TRUSTED_PROXIES` diisi IP proxy (bukan `*`).


## Rilis otomatis: `scripts/deploy.sh`

Urutan: kunci (`flock`) → cadangan DB (`backup:run --only-db`; gagal = rilis dibatalkan) → build image bertag commit →
`migrate --force` memakai image baru **sebelum** container diganti → `up -d` → health check `/api/health` di loopback
(`HTTP_PORT`) → pangkas image lama (sisa 5). Migrasi karenanya harus kompatibel mundur.

```bash
bash scripts/deploy.sh                  # rilis commit yang sedang ter-checkout
bash scripts/deploy.sh --list           # tag image tersedia
bash scripts/deploy.sh --rollback <tag> # kembali ke image lama tanpa build (kode saja, bukan skema DB)
```

Rollback tidak membatalkan migrasi; bila rilis gagal sempat bermigrasi, pulihkan cadangan (`docs/PEMULIHAN.md`).
Variabel opsional: `PUSPRESMA_LEWATI_CADANGAN=1` (server baru tanpa data), `PUSPRESMA_KEEP_IMAGE_TAGS`, `PUSPRESMA_CABANG`.

### Cadangan jalur tarik (cron) bila GitHub tidak dapat menjangkau server

Bila SSH dari GitHub Actions tidak mungkin (mis. server di balik NAT kampus), server menarik sendiri. `--tarik` melakukan
`git fetch` lalu merilis **hanya bila** `origin/main` punya commit baru. Pasang manual di server (tidak dipasang otomatis);
gunakan salah satu jalur, jangan keduanya:

```cron
*/5 * * * * cd ~/docker-apps/puspresma && bash scripts/deploy.sh --tarik >> /var/log/puspresma-deploy.log 2>&1
```

Jalur tarik tidak menunggu CI; lindungi `main` dengan aturan branch (wajib PR + CI hijau) agar hanya kode teruji yang masuk.

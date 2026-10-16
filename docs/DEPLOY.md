# Deploy produksi — Puspresma FKIP Unsil

Pola docker-apps: aplikasi berjalan di belakang reverse proxy fakultas pada `https://supportfkip.unsil.ac.id/puspresma`
(loopback `127.0.0.1:8032`, awalan dipotong). Berkas: `Dockerfile` (target `app` dan `web`), `docker-compose.prod.yml`,
`.env.production.example`, `docker/` (php, nginx, backup).

> **Profil Ringan** (STANDAR-TEKNIS §1b dan §7, keputusan 15–16 Oktober 2026): cache, sesi, antrean, dan lock di MariaDB (driver
> `database`); tanpa Redis, Horizon, maupun container database. Satu container `scheduler` (`schedule:work`) menjalankan jadwal sekaligus
> worker antrean (`--stop-when-empty` tiap menit); antrean `default` dan `berat`. Kesehatan dipantau lewat `/api/health` dan
> `queue:failed` / `queue:retry`. **Peralihan dari kondisi sebelumnya (Redis/Horizon): lihat bagian terakhir.**

## Rantai otomatis (GitHub Actions)

- `.github/workflows/ci.yml`: pada push ke `main` dan setiap pull request menjalankan Pint, Larastan level 6, dan Pest
  (MariaDB 10.11 sebagai service container sementara, hanya untuk CI, PHP 8.5, Node 22; tanpa Redis).
  Service container database di CI tidak berarti produksi memakai container database.
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
cp .env.production.example .env && chmod 600 .env     # isi DB_PASSWORD, SMTP, TRUSTED_PROXIES
APP_VERSION=$(git describe --tags --always) docker compose -f docker-compose.prod.yml build   # ASSET_URL wajib terisi; APP_VERSION tampil di footer dan /api/health
docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate --show   # salin ke APP_KEY dan SIMPAN cadangannya
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan optimize
```

Basis data: MariaDB 10.11 native di host (`db_puspresma`, pengguna khusus), dijangkau lewat `host.docker.internal` (`extra_hosts: host.docker.internal:host-gateway`), sama dengan aplikasi produksi lain (STANDAR-TEKNIS §7). Compose produksi tidak boleh memuat container database.
Compose produksi hanya memuat `app`, `nginx`, `scheduler`, dan `backup` (klien dump); semua memakai `extra_hosts` host-gateway, `mem_limit`, dan rotasi log. Blok reverse proxy ada di `README.md`.
Cadangan harian: `spatie/laravel-backup` lewat scheduler (`backup:run --only-db` 01:00; terenkripsi) — lihat `docs/PEMULIHAN.md`. `docker/backup/backup.sh` tetap tersedia sebagai cadangan kedua di host; alat dump yang dipakai adalah `mariadb-dump`.

## Migrasi, kesehatan, dan volume

- Migrasi otomatis hanya bila `RUN_MIGRATIONS=true` (container `app`); default `false`. Entrypoint menunggu DB, lalu menjalankan `optimize`, `filament:optimize`, dan `icons:cache` (`storage:link` tidak diperlukan: tidak ada berkas publik di server).
- Healthcheck nginx memakai `/api/health` dan mencocokkan `"status":"ok"` persis (503 hanya bila basis data gagal; job tertunda > 10 menit, job gagal 24 jam, dan `detak_penjadwal` `terlambat`/`belum-ada` hanya informatif). MariaDB (di host) tidak dibuka ke jaringan luar; tidak ada Redis.
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

## Peralihan dari Redis/Horizon ke Profil Ringan

**Merge ke `main` memicu deploy otomatis** (CI hijau, lalu `scripts/deploy.sh`). Compose baru tidak punya Redis; `.env` server yang masih
`SESSION_DRIVER=redis` membuat aplikasi tidak bisa jalan. Urutan wajib:

| # | Langkah | Bukti |
|---|---|---|
| 1 | Jadwalkan di luar jam kerja dan umumkan: **semua sesi berakhir**, pengguna masuk ulang | Pengumuman |
| 2 | Cadangan penuh di luar server (`backup:run --only-db` atau `mariadb-dump`); simpan `.env` lama sebagai `.env.sebelum-profil-ringan` dan catat `APP_KEY` | Berkas cadangan |
| 3 | **Kuras antrean lama**: di `/puspresma/horizon` tunggu job tertunda dan berjalan = 0. Job yang tersisa di Redis **tidak** berpindah dan hilang saat container `queue` dihentikan oleh rilis | Tangkapan layar Horizon kosong |
| 4 | Ubah `.env` server: `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, tambah `APP_KODE=puspresma`; hapus `REDIS_*` dan `HORIZON_*` (rujukan: `.env.production.example`). Lakukan langkah 4 dan 5 dalam jendela yang sama | `diff` terhadap `.env.production.example` |
| 5 | Merge ke `main`. `deploy.sh` mencadangkan DB (gagal = rilis batal), menjalankan `migrate --force` dari image baru sebelum container diganti (migrasi `2026_10_16_100000_hapus_izin_horizon` menghapus izin `horizon.akses`), lalu `up -d --remove-orphans` yang menghapus container `queue` dan `redis` lama | Log rilis |
| 6 | Verifikasi: `/api/health` → `database: true`, `detak_penjadwal: ok` (≤ 2 menit); `exec app php artisan schedule:list` memuat dua `queue:work`; kirim notifikasi uji dan satu ekspor besar (antrean `berat`); `docker stats` | Keluaran perintah |
| 7 | Ukur memori selama jam sibuk dan sesuaikan `mem_limit` (nilai awal: app 512m, nginx 64m, scheduler 768m) | Catatan ukur |
| 8 | Setelah 7 hari tanpa masalah: hapus volume `redis-data` lama (`docker volume ls`) | — |

**Pembatalan penuh.** `bash scripts/deploy.sh --rollback <tag>` saja **tidak cukup**: ia hanya mengganti image, sedangkan `docker-compose.prod.yml`
terbaru tidak memuat Redis atau Horizon. Lakukan: `git reset --hard <commit sebelum merge>` di server, kembalikan `.env.sebelum-profil-ringan` ke `.env`, lalu
`bash scripts/deploy.sh`. Migrasi izin tidak dibatalkan otomatis (`down()` hanya membuat ulang izin tanpa relasi peran); setelah kembali ke kode lama jalankan
`php artisan db:seed --class=PeranDanIzinSeeder --force` agar super-admin kembali memegang `horizon.akses`, atau pulihkan cadangan langkah 2.

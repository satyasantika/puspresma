# 04 — Prompt Bertahap Vibecoding Puspresma

Inti paket. Jalankan **berurutan**, satu langkah per sesi agen AI (Claude Code atau sejenis) di root repositori `puspresma`. Sebelum mulai, pastikan `CLAUDE.md` sudah ada di root repo (langkah F0.1) dan Laravel Boost terpasang (F1.4) agar agen memakai dokumentasi versi yang benar.

## Cara memakai

1. Di awal fase: `git switch main && git pull && git switch -c <branch fase>` (fase F0–F1 langsung di `main`).
2. Salin blok **Prompt** ke agen. Biarkan agen bekerja; jawab pertanyaannya bila ada.
3. Periksa daftar **Selesai bila** satu per satu (buka browser, coba login dengan peran yang disebut).
4. Tinjau `git diff`. Bila ada perubahan di luar cakupan, minta agen membatalkannya.
5. Jalankan blok **Commit**. Hook `pre-commit` menjalankan Pint dan test; hook `commit-msg` memeriksa format Conventional Commits.
6. Di langkah **Tutup fase**: push, buat PR, tunggu CI hijau, squash-merge, beri tag.

Rujukan aturan bisnis (BR-xx) ada di `docs/vibecoding/01-PRD.md`; skema di `03-SKEMA-DATABASE.md`; arsitektur di `02-ARSITEKTUR.md`.

## Peta fase

| Fase | Judul | Branch | Langkah | Tag |
|---|---|---|---|---|
| F0 | Persiapan repositori | `main` | F0.1–F0.2 | — |
| F1 | Fondasi Laravel | `main` | F1.1–F1.9 | `v0.1.0` |
| F2 | Autentikasi, peran & audit | `feat/f2-auth-peran` | F2.1–F2.5 | `v0.2.0` |
| F3 | Data master, impor, tautan berkas & login Google | `feat/f3-master-data` | F3.1–F3.9 | `v0.3.0` |
| F4 | Pengajuan prestasi & bukti | `feat/f4-pengajuan-prestasi` | F4.1–F4.6 | `v0.4.0` |
| F5 | Verifikasi, duplikat, pembimbing & poin | `feat/f5-verifikasi` | F5.1–F5.6 | `v0.5.0` |
| F6 | Katalog kompetisi & galeri publik | `feat/f6-katalog-galeri` | F6.1–F6.5 | `v0.6.0` |
| F7 | Notifikasi & penjadwal | `feat/f7-notifikasi-jadwal` | F7.1–F7.4 | `v0.7.0` |
| F8 | Laporan, dasbor & ekspor | `feat/f8-laporan-ekspor` | F8.1–F8.6 | `v0.8.0` |
| F9 | Pengerasan & rilis | `feat/f9-pengerasan` | F9.1–F9.7 | `v1.0.0` |

Total: **10 fase, 59 langkah**.

> **Kebijakan berkas (STANDAR-TEKNIS §1a):** sistem tidak menerima unggahan berkas. Sertifikat/piagam, foto penyerahan, surat tugas, dan poster berupa **tautan** (Google Drive, dsb.) di tabel `tautan_berkas`. Satu-satunya input berkas adalah impor Excel/CSV master data, yang diproses lalu dihapus. Keluaran PDF/Excel di-stream atau disimpan sementara di `storage/app/tmp` ≤ 24 jam.

---

## F0 — Persiapan repositori

### F0.1 — Inisialisasi repositori, hook, dan berkas konteks
**Branch:** `main`
**Tujuan:** Menyiapkan repositori kosong dengan `.gitignore`, hook Git standar, README, `CLAUDE.md`, dan salinan bahan vibecoding.

**Prompt (salin ke agen AI):**
```text
Kita memulai repositori baru bernama "puspresma" (Pusat Prestasi Mahasiswa FKIP Universitas Siliwangi).
Laravel BELUM dipasang; jangan menjalankan composer pada langkah ini.

Kerjakan:
1. Pastikan direktori kerja adalah repo git dengan branch awal "main" (git init -b main bila belum).
2. Buat .gitignore yang mencakup pola standar Laravel (/vendor, /node_modules, /public/build, /public/hot,
   /public/storage, /storage/*.key, .env, .env.backup, .env.production, .phpunit.result.cache, Homestead.*,
   /.idea, /.vscode, npm-debug.log, yarn-error.log) DITAMBAH: storage/app/tmp/* (berkas sementara ekspor/impor), *.sql, *.sql.gz, *.zip,
   auth.json, /storage/app/backup*. Sertakan pengecualian !storage/app/tmp/.gitignore.
3. Buat .githooks/commit-msg dan .githooks/pre-commit dengan isi PERSIS seperti STANDAR-GIT.md bagian 6
   (berkas ada di docs/vibecoding/STANDAR-GIT.md setelah langkah 5). Jadikan executable (chmod +x).
   JANGAN jalankan "git config core.hooksPath" sekarang, karena vendor/ belum ada sehingga pre-commit akan gagal;
   aktivasi dilakukan di F1.1.
4. Buat .editorconfig (utf-8, lf, indent 4 spasi, yaml/json/md 2 spasi).
5. Pindahkan/salin seluruh bahan vibecoding (README.md, 01-PRD.md, 02-ARSITEKTUR.md, 03-SKEMA-DATABASE.md,
   04-PROMPT-BERTAHAP.md, 05-UJI-PENERIMAAN.md, STANDAR-TEKNIS.md, STANDAR-GIT.md) ke docs/vibecoding/.
6. Salin CLAUDE.md dari bahan vibecoding ke root repositori (tanpa diubah).
7. Buat README.md root singkat: nama sistem, tujuan (satu paragraf dari 01-PRD bagian 1), stack
   (Laravel 13, PHP 8.4, MySQL 8.4, Redis 7, Filament 5, Livewire 4), tautan ke docs/vibecoding/, dan bagian
   "Memulai" yang masih bertuliskan "akan dilengkapi di F1".

(Catatan: pint dan test belum tersedia pada langkah ini; cukup tampilkan `git status` dan pohon direktori.)
Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- `git branch --show-current` menampilkan `main`.
- `.githooks/commit-msg` dan `.githooks/pre-commit` ada, executable, isinya sama dengan STANDAR-GIT §6.
- `CLAUDE.md` ada di root; `docs/vibecoding/` berisi 8 berkas.
- `.gitignore` memuat `.env` dan `storage/app/tmp/*`.

**Commit:**
```bash
# pint & test belum ada pada F0 — lewati gerbang kualitas
git add -A
git commit -m "chore: inisialisasi repositori"
```

### F0.2 — Templat pull request dan remote
**Tujuan:** Menyiapkan templat PR berisi daftar periksa standar dan menghubungkan repositori ke remote kampus/GitHub.

**Prompt (salin ke agen AI):**
```text
Buat .github/pull_request_template.md berbahasa Indonesia berisi:
- Judul fase/langkah dan ringkasan perubahan.
- Daftar langkah 04-PROMPT-BERTAHAP yang tercakup (mis. F3.1–F3.7).
- Aturan bisnis (BR-xx) yang disentuh.
- Daftar periksa: [ ] pint --test lolos, [ ] phpstan level 6 lolos, [ ] php artisan test hijau,
  [ ] migrasi baru tidak mengubah migrasi yang sudah dirilis, [ ] policy dipasang di setiap Resource baru,
  [ ] tidak ada .env/kredensial/berkas sementara (storage/app/tmp) ikut ter-commit,
  [ ] tidak ada komponen unggah berkas baru selain impor Excel (kebijakan tautan STANDAR-TEKNIS §1a), [ ] CHANGELOG.md diperbarui.
- Bagian "Cara menguji manual" dengan akun peran yang dipakai.
Buat juga CHANGELOG.md dengan format "Keep a Changelog" berbahasa Indonesia, bagian [Belum dirilis] kosong.

(Catatan: pint dan test belum tersedia; tampilkan isi kedua berkas.)
Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- `.github/pull_request_template.md` dan `CHANGELOG.md` ada.
- Remote `origin` sudah terpasang (`git remote -v`) dan `main` ter-push.
- Di GitHub/GitLab: `main` diproteksi (wajib PR, wajib CI hijau) — dilakukan manusia di pengaturan repo.

**Commit:**
```bash
git add -A
git commit -m "chore: tambah templat pull request dan changelog"
git remote add origin <url-repo>
git push -u origin main
```

---

## F1 — Fondasi Laravel

### F1.1 — Proyek Laravel 13 di kontainer Docker FKIP
**Branch:** `main` (fondasi; commit langsung ke `main` diizinkan sampai F1 selesai)
**Tujuan:** Memasang Laravel 13 ke repositori yang sudah ada dan menjalankannya di Docker FKIP (STANDAR-TEKNIS §2): PHP-FPM, Nginx, dan scheduler per aplikasi; MySQL native di host; Redis & Mailpit bersama. Tanpa Sail, tanpa PHP di host.

Persiapan manual (infrastruktur bersama & database `puspresma`/`puspresma_testing` sudah dibuat menurut STANDAR-TEKNIS §2.2):
```bash
cd ~/code/puspresma
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD":/app -w /app composer:2 \
  create-project laravel/laravel _laravel "^13.0" --ignore-platform-reqs
```

**Prompt (salin ke agen AI):**
```text
Baca CLAUDE.md dan STANDAR-TEKNIS §2–§3 (di docs/). Laravel 13 ada di folder _laravel/ hasil create-project.
Semua perintah PHP/Composer/NPM dijalankan di container: psp = docker compose exec puspresma-php.
1. Pindahkan isi _laravel/ (termasuk berkas tersembunyi) ke root TANPA menimpa README.md, CLAUDE.md, CHANGELOG.md,
   .gitignore, .editorconfig, .gitattributes, .githooks/, .github/, docs/; gabungkan entri .gitignore bawaan Laravel
   yang belum ada ke .gitignore kita; hapus _laravel/. composer.json: "php": "^8.3", "laravel/framework": "^13.0".
2. Buat docker/php/Dockerfile, docker/nginx/default.conf, dan docker-compose.yml PERSIS templat STANDAR-TEKNIS §2.3
   dengan <app>=puspresma dan <port>=8016: service puspresma-php, puspresma-nginx, puspresma-scheduler; jaringan external
   fkip-net; extra_hosts host.docker.internal. JANGAN membuat container MySQL, Redis, atau Mailpit (sudah ada
   bersama) dan JANGAN memasang Laravel Sail. Service puspresma-horizon dibiarkan sebagai komentar sampai Horizon dipasang.
3. .env.example sesuai STANDAR-TEKNIS §3 tanpa nilai rahasia: APP_NAME="Puspresma FKIP Unsil", APP_URL=http://localhost:8016,
   DB_HOST=host.docker.internal, DB_DATABASE=puspresma, DB_USERNAME=puspresma, REDIS_HOST=redis, REDIS_CLIENT=phpredis,
   REDIS_PREFIX=puspresma_, REDIS_DB=40, REDIS_CACHE_DB=41, REDIS_QUEUE_DB=42, CACHE_PREFIX=puspresma_cache_,
   HORIZON_PREFIX=puspresma_horizon:, MAIL_HOST=mailpit, MAIL_PORT=1025. Salin ke .env (DB_PASSWORD diisi manusia,
   jangan di-commit), lalu psp php artisan key:generate.
4. README.md bagian "Menjalankan lokal": prasyarat infrastruktur bersama (STANDAR-TEKNIS §2.2), alias shell
   `alias psp='docker compose exec puspresma-php'`, docker compose up -d --build, psp php artisan migrate,
   psp npm install && psp npm run dev, aplikasi http://localhost:8016, Mailpit http://localhost:8025.
5. config/database.php: MySQL charset utf8mb4 dan collation utf8mb4_0900_ai_ci.
6. README "Memulai" juga memuat `psp php artisan migrate --seed` dan `git config core.hooksPath .githooks`.
Jalankan docker compose up -d --build lalu psp php artisan migrate. Jangan ubah berkas di luar cakupan langkah
ini. Setelah selesai jalankan pint dan test di container (psp ./vendor/bin/pint && psp php artisan test), lalu
tampilkan ringkasan perubahan.
```

**Selesai bila:**
- `docker compose ps` menampilkan `puspresma-php`, `puspresma-nginx`, `puspresma-scheduler` berjalan; tidak ada container MySQL/Redis/Mailpit milik repo ini.
- `psp php artisan --version` = Laravel 13.x; `psp php -m` memuat pdo_mysql, redis, intl, gd, bcmath, pcntl, zip.
- `psp php artisan migrate` sukses ke MySQL host; `http://localhost:8016` menampilkan halaman Laravel.
- `psp php artisan tinker --execute="Cache::put('uji',1); echo Cache::get('uji');"` mencetak `1` (Redis bersama DB 41).
- `.env` tidak muncul di `git status`; `README.md`/`CLAUDE.md`/`docs/` milik kita tidak tertimpa.

**Commit:**
```bash
chmod +x .githooks/*
git config core.hooksPath .githooks
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "build(docker): pasang laravel 13 di kontainer docker fkip"
```

### F1.2 — Locale Indonesia, zona waktu, dan konfigurasi aplikasi
**Tujuan:** Bahasa Indonesia, `Asia/Jakarta`, mode ketat Eloquent, dan berkas `config/puspresma.php`.

**Prompt (salin ke agen AI):**
```text
Atur fondasi konfigurasi Puspresma:
1. config/app.php: 'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'), locale 'id', fallback 'en', faker_locale 'id_ID',
   dan kunci 'version' => env('APP_VERSION', '0.0.0'). Tambahkan APP_TIMEZONE dan APP_VERSION ke .env.example.
2. Publikasikan berkas bahasa (php artisan lang:publish) dan tambahkan lang/id/ (validation.php, auth.php, pagination.php,
   passwords.php) berbahasa Indonesia; lang/id.json untuk string umum.
3. Di AppServiceProvider::boot(): Model::preventLazyLoading(! app()->isProduction()); Model::preventSilentlyDiscardingAttributes(! app()->isProduction());
   Carbon::setLocale('id'); paksa skema https bila production.
4. Buat config/puspresma.php berisi:
   - 'domain_mahasiswa' => env('PUSPRESMA_DOMAIN_MAHASISWA', 'student.unsil.ac.id'),
   - 'domain_pegawai' => env('PUSPRESMA_DOMAIN_PEGAWAI', 'unsil.ac.id'),
   - 'impor' => ['mimes' => ['xlsx','csv'], 'maks_kb' => 10240] (satu-satunya input berkas; bukti prestasi berupa tautan, lihat F3.7),
   - 'antrean' => ['notifikasi' => 'notifikasi', 'ekspor' => 'ekspor', 'impor' => 'impor', 'berkas' => 'berkas'],
   - 'google_login' => env('GOOGLE_LOGIN_ENABLED', false).
   Tambahkan variabel terkait ke .env.example.
5. Tulis test Pest (tests/Feature/KonfigurasiDasarTest.php): timezone Asia/Jakarta, locale id,
   config('puspresma.impor.maks_kb') = 10240, dan pesan validasi 'required' berbahasa Indonesia.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- `psp php artisan tinker --execute="echo now()->translatedFormat('d F Y');"` menampilkan nama bulan Indonesia.
- `config('puspresma.domain_mahasiswa')` = `student.unsil.ac.id`; tidak ada konfigurasi unggah bukti.
- Test `KonfigurasiDasarTest` hijau.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "chore: atur locale id zona waktu dan konfigurasi puspresma"
```

### F1.3 — Redis (cache, sesi, antrean) dan Horizon
**Tujuan:** Memindahkan cache/sesi/antrean ke Redis dengan DB terpisah dan memasang Horizon dengan supervisor sesuai arsitektur.

**Prompt (salin ke agen AI):**
```text
1. .env & .env.example: CACHE_STORE=redis, SESSION_DRIVER=redis, QUEUE_CONNECTION=redis, REDIS_CLIENT=phpredis,
   REDIS_DB=40, REDIS_CACHE_DB=41, REDIS_QUEUE_DB=42. Di config/database.php tambahkan koneksi redis 'queue' (database REDIS_QUEUE_DB)
   dan arahkan config/queue.php koneksi redis ke koneksi 'queue'. Image puspresma-php sudah memasang ekstensi phpredis
   (STANDAR-TEKNIS §2.3).
2. composer require laravel/horizon; php artisan horizon:install.
   Aktifkan service puspresma-horizon di docker-compose.yml (templat STANDAR-TEKNIS §2.3), lalu docker compose up -d.
3. config/horizon.php environments production & local dengan supervisor:
   - 'supervisor-notifikasi': queue ['notifikasi'], maxProcesses 2, tries 3, backoff [60]
   - 'supervisor-umum': queue ['default','berkas'], maxProcesses 2, tries 3
   - 'supervisor-berat': queue ['impor','ekspor'], maxProcesses 1, timeout 600, memory 512, tries 1
4. HorizonServiceProvider::gate(): untuk sementara izinkan hanya di environment local
   (komentar TODO: diganti role super-admin di F2.2).
5. Dokumentasikan di README bahwa antrean berjalan di service puspresma-horizon (docker compose restart puspresma-horizon
   setelah mengubah kode job).
6. Test Pest: config('cache.default') === 'redis', config('queue.default') === 'redis', dan job dummy dapat didispatch ke antrean 'ekspor'
   (Queue::fake, assertPushedOn).

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- `http://localhost:8016/horizon` terbuka di lokal dan menampilkan 3 supervisor.
- `docker exec redis redis-cli -n 41 keys '*'` berisi kunci cache setelah `Cache::put('uji', 1)`.
- `psp php artisan test` hijau.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "build: pasang horizon dan konfigurasi redis cache sesi antrean"
```

### F1.4 — Pest 4, Larastan, Pint, dan Laravel Boost
**Tujuan:** Menyiapkan gerbang kualitas dan bantuan AI versi-spesifik.

**Prompt (salin ke agen AI):**
```text
1. Pasang Pest 4: composer require pestphp/pest:^4 pestphp/pest-plugin-laravel --dev --with-all-dependencies;
   ./vendor/bin/pest --init. Konversi test bawaan ke sintaks Pest. Atur phpunit.xml agar test memakai database
   MySQL host terpisah "puspresma_testing" (DB_HOST=host.docker.internal, DB_DATABASE=puspresma_testing) dan CACHE_STORE=array, QUEUE_CONNECTION=sync, SESSION_DRIVER=array.
   Database dibuat di MySQL host menurut STANDAR-TEKNIS §2.2 (tulis instruksinya di README).
2. Pasang Larastan: composer require larastan/larastan --dev; buat phpstan.neon level 6 dengan paths app/, config/, database/, routes/.
   Perbaiki galat yang muncul pada kode bawaan.
3. Buat pint.json preset "laravel".
4. Pasang Laravel Boost: composer require laravel/boost --dev; php artisan boost:install. Pilih pedoman untuk Laravel, Pest,
   Tailwind (Filament & Livewire akan ditambahkan saat paketnya dipasang). Jangan biarkan boost:install menimpa CLAUDE.md root:
   bila Boost ingin menulis pedoman, gabungkan di bagian akhir CLAUDE.md di bawah judul "Pedoman Laravel Boost".
5. Tambahkan skrip composer: "test": "php artisan test", "lint": "pint --test", "stan": "phpstan analyse --memory-limit=1G".
6. Test Pest arsitektur (tests/ArchTest.php): app/Enums hanya berisi enum, app/Actions tidak memakai facade Request,
   tidak ada dd/dump/ray di app/.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- `psp ./vendor/bin/pest` dan `psp ./vendor/bin/phpstan analyse` hijau.
- `CLAUDE.md` root masih memuat isi asli (tidak tertimpa) + bagian pedoman Boost.
- `.mcp.json` (atau konfigurasi MCP Boost) ada untuk agen.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "build: pasang pest larastan pint dan laravel boost"
```

### F1.5 — Kunci primer UUIDv7
**Tujuan:** Semua tabel aplikasi memakai UUIDv7 sebagai primary key sejak awal (STANDAR-TEKNIS §4a), sebelum tabel domain dibuat.

**Prompt (salin ke agen AI):**
```text
Baca STANDAR-TEKNIS §4a. 1) Migrasi bawaan: users → $table->uuid('id')->primary(); sessions →
foreignUuid('user_id')->nullable()->index(). Model User memakai Illuminate\Database\Eloquent\Concerns\HasUuids
(Laravel 13: UUIDv7). 2) Untuk paket yang SUDAH terpasang (spatie/laravel-permission, spatie/laravel-activitylog,
tabel notifications, Sanctum personal_access_tokens, tabel impor/ekspor Filament) sesuaikan migrasi & model
kustomnya PERSIS §4a butir 5; paket yang dipasang di langkah berikutnya disesuaikan pada langkah pemasangannya.
3) tests/Arch/UuidTest.php: semua kelas di app/Models memakai HasUuids; tidak ada migrasi (kecuali tabel
infrastruktur §4a butir 6) yang memuat ->id(), foreignId(, morphs( tanpa awalan uuid/nullableUuid, bigIncrements(,
atau increments(. 4) UserFactory & seeder tetap berjalan. 5) php artisan migrate:fresh (belum ada data).
Test: User::factory()->create()->id lolos Str::isUuid() dan berversi 7 (karakter ke-15 = '7'); route model
binding dengan UUID berjalan. Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan
test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:** `UuidTest` hijau; kolom `users.id` bertipe CHAR(36); tidak ada `->id()` di migrasi aplikasi.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "refactor(db): pakai uuidv7 sebagai primary key semua tabel"
```

### F1.6 — Siap subpath `https://supportfkip.unsil.ac.id/puspresma`
**Tujuan:** Aplikasi berjalan benar di bawah awalan `/puspresma` — di produksi (reverse proxy) dan di lokal lewat gateway — sehingga URL, aset Vite, cookie, Livewire, Filament, Horizon, dan QR memakai awalan yang sama (STANDAR-TEKNIS §2.4).

Persiapan manual: gateway lokal (STANDAR-TEKNIS §2.4) berjalan dan berisi blok `location /puspresma/`; `puspresma-nginx` tersambung ke jaringan `fkip-net` (templat §2.3).

**Prompt (salin ke agen AI):**
```text
Baca STANDAR-TEKNIS §2.4. Perintah PHP lewat psp (docker compose exec puspresma-php).
1. .env.example (dan .env lokal): APP_URL=http://localhost:8080/puspresma (komentar: produksi
   https://supportfkip.unsil.ac.id/puspresma), ASSET_URL sama dengan APP_URL, SESSION_PATH=/puspresma,
   SESSION_COOKIE=puspresma_session, SESSION_SECURE_COOKIE=false (produksi true), TRUSTED_PROXIES=*.
2. AppServiceProvider::boot(): bila parse_url(APP_URL) memiliki path → URL::forceRootUrl(config('app.url'));
   bila skema https → URL::forceScheme('https'). bootstrap/app.php: trustProxies(at: env('TRUSTED_PROXIES'),
   headers: X-Forwarded-For | Host | Port | Proto | Prefix).
3. Build aset dengan ASSET_URL terisi (psp npm run build) sehingga laravel-vite-plugin memakai base /puspresma/build/;
   catat di README. Bila Livewire/Filament/Horizon sudah terpasang, pastikan URL update & skrip Livewire, aset
   Filament, path panel, dan Horizon berawalan /puspresma (cek API via Boost; Livewire::setUpdateRoute/setScriptRoute bila
   perlu). Bila belum terpasang, langkah pemasangannya wajib mengikuti aturan ini.
4. tests/Arch/SubpathTest.php: pindai resources/views dan resources/js — gagal bila ada href="/…", src="/…",
   action="/…", fetch('/…') atau axios ke "/…" (kecuali "//" dan "#").
5. tests/Feature/SubpathTest.php: dengan config app.url=https://supportfkip.unsil.ac.id/puspresma dan forceRootUrl
   aktif, url('/') dan asset('build/app.js') berawalan https://supportfkip.unsil.ac.id/puspresma; respons halaman awal
   memasang cookie puspresma_session ber-path /puspresma; redirect tamu ke login tetap di bawah /puspresma.
6. README: akses lokal lewat http://localhost:8080/puspresma/ dan blok location reverse proxy produksi (salin §2.4).
Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan
perubahan.
```

**Selesai bila:**
- `http://localhost:8080/puspresma/` tampil lengkap; DevTools Network tidak menunjukkan permintaan ke `/build/…`, `/livewire/…`, atau `/css/…` tanpa awalan `/puspresma`.
- Cookie sesi bernama `puspresma_session` dengan path `/puspresma`; aplikasi lain di gateway tidak saling menimpa sesi.
- `SubpathTest` (arsitektur & feature) hijau.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(config): siapkan aplikasi untuk subpath supportfkip"
```

### F1.7 — Endpoint health dan versi aplikasi
**Tujuan:** Menyediakan `GET /api/health` dan menampilkan versi di footer (kesiapan integrasi FKIP Edu).

**Prompt (salin ke agen AI):**
```text
1. php artisan install:api bila routes/api.php belum ada (tanpa Sanctum token bila tidak perlu; bila Sanctum terpasang otomatis, biarkan).
2. Buat App\Http\Controllers\HealthController (invokable) untuk GET /api/health (route name 'health') yang mengembalikan JSON:
   {"status":"ok|degraded","app":"Puspresma FKIP Unsil","version":config('app.version'),"time":ISO8601,
    "checks":{"database":bool,"redis":bool,"queue_size":{"notifikasi":int,"ekspor":int}}}
   HTTP 200 bila database & redis ok, 503 bila salah satu gagal. Jangan bocorkan pesan exception.
3. Buat layout Blade resources/views/components/layouts/publik.blade.php (Tailwind 4 via Vite) dengan footer
   "Puspresma FKIP Unsil · versi {{ config('app.version') }}". Ganti welcome.blade.php agar memakai layout ini
   (isi sementara: judul dan deskripsi singkat).
4. Test Pest: /api/health mengembalikan 200 dan struktur JSON; bila Redis dimock gagal mengembalikan 503;
   halaman / menampilkan teks versi.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- `curl localhost:8016/api/health` mengembalikan `"status":"ok"`.
- Footer beranda menampilkan versi.
- Test hijau.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(api): tambah endpoint health dan versi aplikasi"
```

### F1.8 — Continuous Integration
**Tujuan:** Setiap push/PR menjalankan Pint, Larastan, dan Pest dengan MySQL 8.4 + Redis 7.

**Prompt (salin ke agen AI):**
```text
Buat .github/workflows/ci.yml:
- Pemicu: push ke main dan pull_request ke main.
- Job "kualitas" di ubuntu-latest dengan services mysql:8.4 (MYSQL_DATABASE=puspresma_test, healthcheck mysqladmin ping)
  dan redis:7-alpine.
- Langkah: checkout, setup-php (php 8.4, ekstensi mbstring, pdo_mysql, redis, gd, zip, intl, bcmath), cache composer,
  composer install --no-interaction --prefer-dist, cp .env.example .env, php artisan key:generate,
  setup-node 22 + npm ci + npm run build, ./vendor/bin/pint --test, ./vendor/bin/phpstan analyse --memory-limit=1G,
  php artisan test --parallel dengan env DB_HOST=127.0.0.1, DB_DATABASE=puspresma_test, REDIS_HOST=127.0.0.1.
- Batasi concurrency per branch (cancel-in-progress).
Tambahkan badge CI di README.md.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Setelah push, workflow CI berjalan hijau di tab Actions.
- README menampilkan badge CI.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "ci: tambah workflow pint phpstan dan pest"
git push
```

### F1.9 — Tutup fase F1
**Tujuan:** Memastikan fondasi bersih lalu memberi tag `v0.1.0`.

**Prompt (salin ke agen AI):**
```text
Lakukan pemeriksaan akhir fase F1:
1. Jalankan ./vendor/bin/pint --test, ./vendor/bin/phpstan analyse --memory-limit=1G, php artisan test; perbaiki bila ada yang merah
   tanpa menambah fitur baru.
2. Pastikan .env tidak terlacak git (git ls-files .env harus kosong) dan .env.example lengkap.
3. Perbarui CHANGELOG.md: pindahkan isi fase ini ke bagian "## [0.1.0] - <tanggal hari ini>" dengan subbagian Ditambahkan
   (Laravel 13 di Docker FKIP, Redis, Horizon, Pest, Larastan, Boost, CI, health endpoint).
4. Tampilkan ringkasan kondisi repo dan daftar perintah tag yang perlu dijalankan manusia.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- CI di `main` hijau.
- `CHANGELOG.md` memuat `[0.1.0]`.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "docs(changelog): catat rilis fondasi v0.1.0"
git push origin main
git tag -a v0.1.0 -m "Fase 1: fondasi Laravel"
git push origin main --tags
```

---

## F2 — Autentikasi, peran & audit

### F2.1 — Filament 5 dengan panel `admin` dan `portal`
**Branch:** `feat/f2-auth-peran`
**Tujuan:** Memasang Filament 5 dengan dua panel dan memperluas tabel `users` sesuai skema.

**Prompt (salin ke agen AI):**
```text
Gunakan Laravel Boost (search-docs) untuk memastikan sintaks Filament 5 dan Livewire 4 yang benar.

1. composer require filament/filament:"^5.0"; pasang panel "admin" (path /admin, id admin) dan buat panel kedua "portal"
   (path /portal, id portal). Warna utama berbeda (admin: indigo, portal: emerald). Brand name "Puspresma FKIP Unsil".
   Aktifkan databaseNotifications() di kedua panel. Discovery resource: app/Filament/Admin/* untuk panel admin
   dan app/Filament/Portal/* untuk panel portal.
2. Migrasi baru add_kolom_puspresma_to_users_table: password nullable, google_id VARCHAR(64) unique nullable,
   npm VARCHAR(20) nullable index, nidn VARCHAR(20) nullable index, nip VARCHAR(30) nullable index,
   nomor_wa VARCHAR(20) nullable, notif_wa_aktif boolean default false, is_active boolean default true index,
   last_login_at datetime nullable. (Kolom prodi_id ditambahkan di F3.1 setelah tabel prodi ada; kolom MFA di F2.3.)
3. User implements FilamentUser; canAccessPanel() sementara: is_active wajib true; panel admin dan portal sama-sama
   diizinkan (akan dipersempit di F2.2). Cast boolean/datetime yang sesuai. Catat last_login_at saat event Login.
4. Pemeriksaan is_active: pengguna nonaktif ditolak login dengan pesan "Akun Anda dinonaktifkan".
5. Test Pest: halaman /admin/login dan /portal/login 200; pengguna nonaktif tidak dapat masuk; last_login_at terisi setelah login.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- `/admin/login` dan `/portal/login` tampil dengan judul Puspresma.
- Tabel `users` memiliki kolom `google_id`, `npm`, `nidn`, `nip`, `nomor_wa`, `notif_wa_aktif`, `is_active`, `last_login_at`.
- Test hijau.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(auth): pasang filament 5 dengan panel admin dan portal"
```

### F2.2 — Peran & izin (spatie/laravel-permission)
**Tujuan:** Enam peran dan seluruh permission sesuai PRD §3, beserta pembatasan akses panel.

**Prompt (salin ke agen AI):**
```text
1. composer require spatie/laravel-permission; publikasikan migrasi & config; User memakai HasRoles.
   Sesuaikan migrasi & model paket ini ke UUID PERSIS STANDAR-TEKNIS §4a butir 5 sebelum migrate.
2. Buat database/seeders/PeranDanIzinSeeder.php:
   Peran: super-admin, admin-fakultas, admin-prodi, pimpinan, dosen, mahasiswa.
   Permission (guard web): pengguna.kelola, master.kelola, mahasiswa.impor, dosen.impor, prestasi.lihat-semua,
   prestasi.lihat-prodi, prestasi.lihat-bimbingan, prestasi.buat-milik, prestasi.buat-atas-nama, prestasi.verifikasi-prodi,
   prestasi.verifikasi, prestasi.buka-verifikasi, duplikat.tinjau, pembimbing.konfirmasi, poin.kelola, poin.hitung-ulang,
   kompetisi.kelola, kompetisi.kelola-prodi, galeri.moderasi, laporan.lihat, laporan.ekspor, simkatmawa.ekspor,
   surat.terbitkan, pengaturan.kelola, audit.lihat, horizon.akses.
   Pemetaan:
   - super-admin: semua permission (dan Gate::before mengembalikan true untuk super-admin KECUALI aksi verifikasi
     prestasi; super-admin tidak memverifikasi prestasi — buat pengecualian eksplisit untuk ability 'verifikasi', 'loloskanProdi',
     'bukaVerifikasi').
   - admin-fakultas: master.kelola, mahasiswa.impor, dosen.impor, prestasi.lihat-semua, prestasi.buat-atas-nama,
     prestasi.verifikasi, prestasi.buka-verifikasi, duplikat.tinjau, poin.kelola, poin.hitung-ulang, kompetisi.kelola,
     galeri.moderasi, laporan.lihat, laporan.ekspor, simkatmawa.ekspor, surat.terbitkan, pengaturan.kelola, audit.lihat.
   - admin-prodi: prestasi.lihat-prodi, prestasi.verifikasi-prodi, kompetisi.kelola-prodi, laporan.lihat, laporan.ekspor.
   - pimpinan: prestasi.lihat-semua, laporan.lihat, laporan.ekspor.
   - dosen: prestasi.lihat-bimbingan, pembimbing.konfirmasi.
   - mahasiswa: prestasi.buat-milik.
   Seeder idempoten (firstOrCreate) dan memanggil app()[PermissionRegistrar::class]->forgetCachedPermissions().
3. SuperAdminSeeder: membuat/menautkan akun super-admin dari env SEED_SUPERADMIN_EMAIL dan SEED_SUPERADMIN_NAME,
   kata sandi acak 20 karakter dicetak sekali ke konsol. Daftarkan keduanya di DatabaseSeeder.
4. User::canAccessPanel(): panel 'admin' hanya untuk super-admin, admin-fakultas, admin-prodi, pimpinan;
   panel 'portal' hanya untuk mahasiswa, dosen. Bila pengguna membuka panel yang salah setelah login, arahkan ke panel yang benar
   (middleware App\Http\Middleware\ArahkanKePanelSesuaiPeran).
5. Gate viewHorizon: hanya pengguna dengan permission horizon.akses (hapus TODO dari F1.3).
6. Test Pest (tests/Feature/Auth/PeranTest.php): seeder membuat 6 peran; mahasiswa tidak bisa membuka /admin (diarahkan ke /portal);
   admin-prodi tidak bisa membuka /portal; hanya super-admin yang bisa membuka /horizon; super-admin tetap ditolak ability 'verifikasi'.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- `psp php artisan db:seed --class=PeranDanIzinSeeder` dua kali tidak menimbulkan duplikat.
- Login sebagai mahasiswa ke `/admin` dialihkan ke `/portal`.
- `/horizon` 403 untuk `admin-fakultas`.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(auth): tambah peran dan izin dengan spatie permission"
```

### F2.3 — Jejak audit, MFA admin, dan rate limit login
**Tujuan:** Activitylog untuk model penting, MFA wajib bagi `super-admin`/`admin-fakultas`, dan pembatasan percobaan login.

**Prompt (salin ke agen AI):**
```text
1. composer require spatie/laravel-activitylog; publikasikan migrasi & config. Retensi 'delete_records_older_than_days' => 730
   (beri komentar: perlu ditetapkan fakultas). User memakai LogsActivity: log name, email, is_active, perubahan peran;
   JANGAN pernah mencatat password, remember_token, app_authentication_secret, app_authentication_recovery_codes.
   Sesuaikan migrasi & model paket ini ke UUID PERSIS STANDAR-TEKNIS §4a butir 5 sebelum migrate.
2. Catat event login berhasil, login gagal, dan logout ke activitylog (log_name 'auth') dengan IP dan user agent.
3. MFA: gunakan fitur Multi-Factor Authentication bawaan Filament 5 (cek dokumentasi via Boost) berbasis aplikasi authenticator
   (TOTP) + recovery codes. Tambahkan migrasi kolom yang disyaratkan Filament (mis. app_authentication_secret,
   app_authentication_recovery_codes, keduanya terenkripsi). Aktifkan di panel admin; WAJIBKAN untuk peran super-admin dan
   admin-fakultas (bila belum diatur, paksa ke halaman pengaturan MFA). Peran lain opsional.
4. Rate limiter 'login': 5 percobaan per menit per kombinasi email+IP; terapkan pada login kedua panel. Pesan galat Indonesia.
5. Buat Resource read-only App\Filament\Admin\Resources\ActivityLogResource (grup Sistem) untuk permission audit.lihat:
   kolom waktu, pengguna, log, deskripsi, subjek; filter log_name dan rentang tanggal; detail menampilkan properties
   (old/new) dalam tabel.
6. Test Pest: login gagal ke-6 dalam semenit ditolak (429/ pesan throttle); activity_log mencatat login tanpa memuat password;
   admin-fakultas tanpa MFA diarahkan ke pengaturan MFA; admin-prodi tidak dapat membuka ActivityLogResource.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Login admin-fakultas pertama kali meminta pemindaian QR authenticator.
- Menu Sistem → Log Aktivitas menampilkan event login.
- Percobaan login salah keenam diblokir sementara.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(audit): pasang activitylog wajibkan mfa admin dan batasi login"
```

### F2.4 — Pengelolaan pengguna
**Tujuan:** Super admin dapat membuat akun lokal, memberi peran, menonaktifkan akun.

**Prompt (salin ke agen AI):**
```text
Buat App\Filament\Admin\Resources\UserResource (grup Sistem, label "Pengguna") dengan App\Policies\UserPolicy:
- viewAny/view: pengguna.kelola (super-admin) — admin-fakultas boleh viewAny/view saja (tanpa ubah) melalui permission
  tambahan logic di policy: admin-fakultas dapat melihat daftar pengguna.
- create/update/delete: hanya pengguna.kelola. Tidak boleh menghapus diri sendiri; tidak boleh mencabut peran super-admin
  terakhir.
Form: name, email (unik), password (opsional; bila kosong saat create, buat acak dan kirim tautan reset kata sandi lewat surel antrean),
npm, nidn, nip, nomor_wa, is_active, roles (CheckboxList dari spatie). Tabel: nama, email, peran (badge), aktif (toggle),
login terakhir; filter peran dan aktif; aksi "Reset MFA" (hapus secret MFA, tercatat di activitylog) khusus super-admin.
Pencarian nama/email/npm/nidn.
Test Pest: super-admin dapat membuat pengguna dengan peran admin-prodi; admin-fakultas dapat melihat tetapi tidak dapat mengubah;
super-admin tidak dapat menghapus dirinya; reset MFA tercatat di activity_log.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Super admin membuat akun `admin-prodi` baru dan akun tersebut dapat login ke `/admin`.
- Tombol hapus tidak tersedia pada baris diri sendiri.
- Test hijau.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(auth): tambah pengelolaan pengguna dan reset mfa"
```

### F2.5 — Tutup fase F2
**Tujuan:** Verifikasi menyeluruh fase autentikasi, PR, merge, tag `v0.2.0`.

**Prompt (salin ke agen AI):**
```text
Tutup fase F2:
1. Jalankan pint --test, phpstan, test; perbaiki tanpa menambah fitur.
2. Pastikan setiap Resource Filament yang ada memakai Policy (cek dengan test arsitektur: setiap kelas di app/Filament/*/Resources
   memiliki model yang terdaftar policy-nya).
3. Perbarui CHANGELOG.md bagian [0.2.0]: panel admin & portal, peran & izin, activitylog, MFA, rate limit login, pengelolaan pengguna.
4. Tuliskan deskripsi PR (Bahasa Indonesia) mengikuti .github/pull_request_template.md, cetak ke layar.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- CI hijau di PR.
- Reviewer mencoba login 3 peran (super-admin, admin-fakultas dengan MFA, mahasiswa) tanpa masalah.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "docs(changelog): catat fase 2 autentikasi peran dan audit"
git push -u origin feat/f2-auth-peran
gh pr create --base main --title "Fase 2: autentikasi, peran & audit" --body-file pr-f2.md
# setelah review & CI hijau
gh pr merge --squash --subject "feat(auth): fase 2 autentikasi peran dan audit" --delete-branch
git switch main && git pull
git tag -a v0.2.0 -m "Fase 2: autentikasi, peran & audit"
git push origin main --tags
```

---

## F3 — Data master, impor, tautan berkas & login Google

### F3.1 — Tabel prodi, mahasiswa, dosen
**Branch:** `feat/f3-master-data`
**Tujuan:** Membuat master akademik inti beserta model, factory, dan seeder prodi dari CSV.

**Prompt (salin ke agen AI):**
```text
Ikuti docs/vibecoding/03-SKEMA-DATABASE.md bagian 3.2–3.4 dan 3.1 (kolom prodi_id).
1. Migrasi: create_prodi_table, create_mahasiswa_table, create_dosen_table, add_prodi_id_to_users_table
   (FK prodi.id nullOnDelete). Tipe, null, default, unique, index persis sesuai dokumen. Soft delete untuk ketiganya.
2. Enum App\Enums\JenjangProdi dan App\Enums\StatusMahasiswa (backed string, method label()).
3. Model Prodi, Mahasiswa, Dosen dengan relasi (Prodi hasMany Mahasiswa/Dosen; Mahasiswa belongsTo Prodi & User;
   Dosen belongsTo Prodi & User; User belongsTo Prodi, hasOne Mahasiswa, hasOne Dosen). Accessor Dosen::nama_lengkap
   (gelar_depan + nama + gelar_belakang). Cast enum. LogsActivity untuk ketiganya (kecuali nomor_hp tidak dicatat nilainya).
4. Factory ketiganya (faker id_ID; npm 10 digit; email mahasiswa {npm}@student.unsil.ac.id).
5. ProdiSeeder membaca database/seeders/data/prodi.csv (header: kode,kode_pddikti,nama,jenjang) dan upsert berdasarkan kode.
   Isi CSV hanya 2 baris contoh dengan nama "CONTOH Pendidikan A" dan "CONTOH Pendidikan B" serta komentar README di
   database/seeders/data/README.md bahwa fakultas wajib mengganti dengan data resmi. Daftarkan di DatabaseSeeder.
6. Global helper App\Support\ProdiScope::untuk(User $user, Builder $query, string $kolom = 'prodi_id'): bila user hanya admin-prodi
   (tanpa permission lihat-semua), batasi ke $user->prodi_id; bila prodi_id null, kembalikan hasil kosong.
7. Test Pest: relasi, unique npm, ProdiSeeder idempoten, ProdiScope membatasi admin-prodi dan tidak membatasi admin-fakultas.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Tabel `prodi`, `mahasiswa`, `dosen` ada; `users.prodi_id` ada dengan FK.
- `psp php artisan db:seed --class=ProdiSeeder` memuat baris dari CSV.
- Test `ProdiScope` hijau.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(db): tambah tabel prodi mahasiswa dan dosen"
```

### F3.2 — Master jenis kegiatan, kategori lomba, tingkat, capaian
**Tujuan:** Master klasifikasi prestasi yang selaras SIMKATMAWA, dengan cache Redis.

**Prompt (salin ke agen AI):**
```text
Ikuti 03-SKEMA-DATABASE.md bagian 3.5–3.8 dan bagian 5 (seeder).
1. Migrasi create_jenis_kegiatan_table, create_kategori_lomba_table, create_tingkat_table, create_capaian_table
   (kolom kode_simkatmawa nullable di semuanya; soft delete; unique sesuai dokumen).
2. Model JenisKegiatan (hasMany KategoriLomba, Capaian), KategoriLomba, Tingkat, Capaian (belongsTo JenisKegiatan).
   Scope aktif() dan terurut() (urutan, nama).
3. Seeder JenisKegiatanSeeder (kompetisi, rekognisi), KategoriLombaSeeder, TingkatSeeder (lokal=1, wilayah=2, nasional=3,
   internasional=4), CapaianSeeder (kompetisi: juara-1, juara-2, juara-3 [is_juara=true], harapan-1..3, penghargaan-khusus,
   finalis, peserta; rekognisi: narasumber, juri, karya-diakui, penghargaan-lembaga, lainnya). Semua upsert berdasarkan kode.
   Tambahkan komentar di setiap seeder: "Selaraskan dengan pedoman SIMKATMAWA terbaru (perlu verifikasi)".
4. Cache: App\Observers\MasterObserver dipasang pada keempat model; saat saved/deleted/restored menghapus kunci
   master:jenis-kegiatan, master:kategori-lomba, master:tingkat, master:capaian:{jenis_kegiatan_id}.
   Buat App\Services\OpsiMaster dengan method jenisKegiatan(), kategoriLomba(?int $jenisId), tingkat(), capaian(int $jenisId)
   yang memakai Cache::rememberForever dengan kunci tersebut dan mengembalikan array id => nama (hanya aktif).
5. Test Pest: seeder membuat 4 tingkat dan capaian rekognisi tidak muncul di OpsiMaster::capaian(id kompetisi);
   mengubah nama tingkat menghapus cache sehingga OpsiMaster mengembalikan nama baru.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Empat tabel master berisi data seeder.
- `docker exec redis redis-cli -n 41 keys '*master*'` menampilkan kunci setelah OpsiMaster dipanggil, dan hilang setelah data diubah.
- Test hijau.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(db): tambah master jenis kegiatan kategori tingkat dan capaian"
```

### F3.3 — Resource Filament data master
**Tujuan:** Admin fakultas mengelola master; admin prodi melihat mahasiswa & dosen prodinya saja.

**Prompt (salin ke agen AI):**
```text
Di panel admin, grup navigasi "Master":
1. ProdiResource, JenisKegiatanResource, KategoriLombaResource, TingkatResource, CapaianResource — CRUD untuk permission master.kelola,
   kolom kode_simkatmawa ditampilkan dengan helper text "Isi sesuai kode/label SIMKATMAWA (perlu verifikasi)". Soft delete dengan
   filter Trashed, aksi restore. Satu App\Policies\MasterPolicy dipakai (Gate::policy) untuk lima model tersebut: viewAny untuk
   semua peran panel admin, create/update/delete/restore hanya master.kelola. CapaianResource: filter jenis kegiatan; reorder by urutan.
2. MahasiswaResource: kolom npm, nama, prodi, angkatan, status, email; filter prodi, angkatan, status; pencarian npm/nama.
   Query di-scope dengan ProdiScope (admin-prodi hanya prodinya). Kolom email & nomor_hp hanya terlihat bila user punya
   master.kelola (BR-26). MahasiswaPolicy: viewAny = master.kelola atau prestasi.lihat-prodi; view record = master.kelola atau
   prodi sama; create/update/delete = master.kelola. Aksi "Buat akun" (membuat User dengan email mahasiswa, peran mahasiswa,
   password null) bila user_id kosong.
3. DosenResource: setara MahasiswaResource (nidn, nip, nama lengkap, prodi, email); aksi "Buat akun" memberi peran dosen.
4. Test Pest (Livewire test Filament): admin-fakultas dapat membuat tingkat; admin-prodi tidak melihat tombol create;
   admin-prodi prodi A hanya melihat mahasiswa prodi A di tabel dan mendapat 403 saat membuka halaman view mahasiswa prodi B
   (IDOR); pimpinan tidak melihat kolom email.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Menu Master tampil untuk admin fakultas; admin prodi hanya melihat Mahasiswa & Dosen (read-only).
- Admin prodi tidak dapat membuka mahasiswa prodi lain lewat URL.
- Test hijau.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(master): tambah resource data master dengan cakupan prodi"
```

### F3.4 — Impor mahasiswa & dosen dari Excel
**Tujuan:** Mengimpor master mahasiswa (NPM, nama, prodi, angkatan, email) dan dosen dari XLSX/CSV lewat antrean. Ini satu-satunya input berkas yang diizinkan (STANDAR-TEKNIS §1a): berkas diproses lalu dihapus, tidak disimpan.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-19, STANDAR-TEKNIS §1a (impor Excel diproses langsung lalu dihapus).
1. composer require spatie/simple-excel. config/filesystems.php: disk 'tmp' (driver local, root storage_path('app/tmp'), private).
   Migrasi create_riwayat_impor_table sesuai 03-SKEMA bagian 3.22 (path_tmp, path_galat, kedaluwarsa_at); model RiwayatImpor.
2. Page Filament App\Filament\Admin\Pages\ImporMahasiswa (permission mahasiswa.impor): unduh templat XLSX yang di-stream langsung
   (kolom: npm, nama, kode_prodi, angkatan, email, status_mahasiswa), input berkas impor (hanya xlsx/csv, maks 10 MB) yang disimpan
   ke disk 'tmp' folder impor/, tombol Proses. Saat diproses: buat RiwayatImpor status antre lalu dispatch
   App\Jobs\ImporMahasiswaDariExcel ke antrean 'impor'. Tampilkan tabel riwayat impor (status, total, berhasil, gagal, unduh berkas
   galat selama belum kedaluwarsa).
3. Job ImporMahasiswaDariExcel: ambil Cache::lock('lock:impor:mahasiswa', 900) — bila gagal, set status gagal dengan pesan
   "Impor lain sedang berjalan". Baca baris dengan SimpleExcelReader (stream), validasi per baris: npm wajib (angka, 8–20 digit),
   nama wajib, kode_prodi harus ada di tabel prodi, angkatan 4 digit antara 1990 dan tahun berjalan, email opsional tetapi bila
   diisi harus berdomain config('puspresma.domain_mahasiswa'), status_mahasiswa harus nilai enum (default aktif).
   Upsert per chunk 500 berdasarkan npm (tidak menghapus apa pun). Baris gagal ditulis ke XLSX galat (kolom asli + alasan) di disk
   'tmp' dengan kedaluwarsa_at = now + 24 jam. Di blok finally HAPUS berkas sumber dan set path_tmp = null (berhasil maupun gagal).
   Perbarui hitungan di RiwayatImpor; kirim notifikasi database ImporSelesai ke pemohon.
4. Buat padanan untuk dosen: Page ImporDosen + Job ImporDosenDariExcel (kolom: nidn, nip, nama, gelar_depan, gelar_belakang,
   kode_prodi, email; kunci upsert nidn bila ada, jika tidak nip; email domain config('puspresma.domain_pegawai')).
5. Test Pest dengan berkas fixture yang dibuat di test (SimpleExcelWriter): 3 baris valid + 1 kode prodi salah → 3 berhasil, 1 gagal,
   berkas galat ada di disk tmp; berkas sumber sudah terhapus setelah job selesai (juga saat job gagal); impor ulang tidak menggandakan;
   lock mencegah impor paralel; berkas .pdf ditolak di input impor; admin-prodi 403 pada halaman impor.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Templat terisi 1.000 baris diproses di Horizon (antrean `impor`) tanpa timeout.
- Setelah selesai, `storage/app/tmp/impor/` tidak lagi berisi berkas sumber.
- Berkas galat dapat diunduh (≤ 24 jam) dan menjelaskan alasan tiap baris.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(impor): impor mahasiswa dan dosen dari excel lewat antrean"
```

### F3.5 — Pengaturan sistem
**Tujuan:** Kunci-nilai konfigurable (tahap verifikasi prodi, poin, galeri, ambang duplikat) dengan cache.

**Prompt (salin ke agen AI):**
```text
1. Migrasi create_pengaturan_table sesuai 03-SKEMA bagian 3.10; model Pengaturan (LogsActivity, catat nilai lama/baru).
2. App\Services\Pengaturan: method statis ambil(string $kunci, mixed $bawaan = null) yang membaca seluruh tabel dari
   Cache::rememberForever('pengaturan:semua') dan meng-cast sesuai kolom tipe (bool/int/decimal sebagai string numerik/json);
   setel(string $kunci, mixed $nilai) yang menyimpan, mencatat activitylog, dan menghapus cache.
3. PengaturanSeeder dengan kunci dan nilai bawaan persis seperti 03-SKEMA bagian 5 (verifikasi_prodi_aktif=false, poin_aktif=false,
   mode_poin_tim=penuh, galeri_aktif=true, ambang_negara_internasional=3, ambang_pt_nasional kosong, ambang_kemiripan_duplikat=0.80,
   rentang_hari_duplikat=7, batas_klaim_bulan=24, awal_tahun_akademik_bulan=8, wa_aktif=false,
   teks_persetujuan_publikasi berisi draf teks persetujuan publikasi nama, prodi, judul kegiatan, capaian, dan foto penyerahan
   di galeri publik FKIP, dapat ditarik kapan saja). Beri keterangan pada kunci yang perlu verifikasi.
4. Page Filament App\Filament\Admin\Pages\PengaturanSistem (grup Sistem, permission pengaturan.kelola) dengan section:
   Verifikasi (toggle verifikasi_prodi_aktif), Poin (toggle poin_aktif, select mode_poin_tim), Galeri (toggle galeri_aktif,
   textarea teks_persetujuan_publikasi), Duplikat (ambang 0.50–1.00, rentang hari 0–60), Validasi (ambang negara, ambang PT,
   batas klaim bulan, awal tahun akademik), WhatsApp (wa_aktif). (Retensi berkas sementara tetap 24 jam sesuai STANDAR-TEKNIS, tidak dapat diubah.) Simpan memanggil Pengaturan::setel.
5. Test Pest: ambil() mengembalikan bool untuk tipe bool; setel() menghapus cache dan tercatat di activity_log; admin-prodi 403
   pada halaman pengaturan.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Halaman Pengaturan Sistem tampil untuk admin fakultas dan perubahan langsung berlaku (tanpa restart).
- Log aktivitas mencatat perubahan pengaturan.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(pengaturan): tambah pengaturan sistem berbasis kunci nilai"
```

### F3.6 — Bobot poin prestasi (opsional, konfigurable)
**Tujuan:** Tabel bobot per jenis kegiatan × tingkat × capaian dengan masa berlaku dan halaman matriks.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-18.
1. Migrasi create_bobot_poin_table sesuai 03-SKEMA bagian 3.9 (poin DECIMAL(8,2), unique kombinasi + berlaku_mulai).
   Model BobotPoin (cast poin 'decimal:2', LogsActivity), BobotPoinObserver menghapus cache master:bobot-poin.
2. App\Services\PenghitungPoin::bobot(int $jenisId, int $tingkatId, int $capaianId, CarbonInterface $tanggal): ?string
   — memilih baris dengan berlaku_mulai <= tanggal dan (berlaku_sampai null atau >= tanggal), terbaru berlaku_mulai; data dibaca
   dari cache master:bobot-poin. Kembalikan string desimal (bukan float).
3. BobotPoinResource (grup Poin, permission poin.kelola) CRUD biasa + Page MatriksBobotPoin: pilih jenis kegiatan dan
   tanggal berlaku_mulai, tampilkan grid baris=capaian, kolom=tingkat berisi input angka (2 desimal); tombol Simpan membuat
   baris baru untuk berlaku_mulai tersebut dan mengisi berlaku_sampai baris lama = berlaku_mulai - 1 hari (dalam transaksi).
   Kolom dasar_penetapan wajib (mis. nomor SK Dekan).
4. BobotPoinContohSeeder (TIDAK didaftarkan di DatabaseSeeder) berisi nilai contoh dari 03-SKEMA bagian 5 dengan komentar
   "contoh, bukan acuan resmi".
5. Test Pest: bobot() memilih periode yang benar pada dua periode berbeda; simpan matriks menutup periode lama;
   hasil bertipe string "40.00"; admin-prodi 403.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Halaman Matriks Bobot Poin menampilkan grid capaian × tingkat dan menyimpan periode baru.
- Tidak ada kolom `FLOAT`/`DOUBLE` di migrasi.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(poin): tambah matriks bobot poin per tingkat dan capaian"
```

### F3.7 — Fondasi tautan berkas (`tautan_berkas`, tanpa unggah ke server)
**Tujuan:** Menyiapkan infrastruktur tautan bersama (STANDAR-TEKNIS §1a) yang dipakai bukti prestasi (F4) dan poster kompetisi (F6).

**Prompt (salin ke agen AI):**
```text
Ikuti STANDAR-TEKNIS.md §1a dan 03-SKEMA-DATABASE.md bagian 3.14. Sistem TIDAK menerima unggahan berkas; hanya tautan.
1. config/berkas.php: 'mode' => env('BERKAS_MODE', 'tautan'); 'domain_diizinkan' => ['drive.google.com','docs.google.com',
   '*.unsil.ac.id'] (opsional 'onedrive.live.com', '*.sharepoint.com' dikomentari); 'pemendek_ditolak' => ['bit.ly','s.id','tinyurl.com',
   'goo.gl','t.co','cutt.ly','rebrand.ly']; 'timeout_cek' => 10. Tambahkan BERKAS_MODE=tautan ke .env.example.
2. Enum App\Enums\JenisTautan (sertifikat_piagam, foto_penyerahan, surat_tugas, lainnya, poster), PenyediaTautan, StatusCekTautan.
3. Migrasi create_tautan_berkas_table persis sesuai 03-SKEMA 3.14 (kolom inti standar + perluasan Puspresma tampil_publik, url_normal;
   ENUM untuk penyedia & status_cek; indeks morph + jenis, drive_file_id, url_normal(191)). Model App\Models\TautanBerkas (HasUuids, SoftDeletes, morphTo pemilik, LogsActivity hanya kolom url/jenis/label/tampil_publik dengan old→new). Daftarkan
   Relation::enforceMorphMap(['prestasi' => Prestasi::class, 'kompetisi' => Kompetisi::class]) — kelas yang belum ada boleh ditambahkan
   ke peta saat fasenya (beri komentar).
4. App\Rules\TautanBerkasValid (rule Laravel + dipakai di Filament ->rule()): hanya skema https; host cocok daftar putih (wildcard
   *.unsil.ac.id); tolak host pemendek; tolak tautan folder Drive (/drive/folders/, /drive/u/*/folders/) dengan pesan "Gunakan tautan ke
   satu berkas, bukan folder"; panjang ≤ 2048. Pesan galat bahasa Indonesia.
5. App\Contracts\PenyimpananBerkas dan implementasi App\Services\Berkas\TautanEksternal (binding di AppServiceProvider berdasarkan
   config('berkas.mode'); mode selain 'tautan' melempar exception "belum didukung"): simpanTautan(Model $pemilik, JenisTautan $jenis,
   string $url, ?string $label, User $u): TautanBerkas — validasi, normalisasi (url_normal: host huruf kecil, hapus usp=sharing dan
   parameter utm_*, hapus fragmen), tentukan penyedia, ekstrak drive_file_id dari pola /file/d/<id>, open?id=<id>, uc?id=<id>,
   /document|spreadsheets|presentation/d/<id>; urlBuka(), urlPratinjau() (https://drive.google.com/file/d/<id>/preview untuk Drive),
   urlGambar() (https://lh3.googleusercontent.com/d/<id> untuk Drive).
6. Job App\Jobs\PeriksaTautanBerkas(TautanBerkas) antrean 'berkas', unique per tautan 10 menit: Http::timeout(10) dengan maksimal 3 redirect; HEAD lalu fallback GET dengan header Range bytes=0-0; tanpa cookie/login; JANGAN menyimpan
   isi respons. dapat_diakses bila status akhir 200/206 dan host akhir bukan accounts.google.com (halaman login); selain itu
   tidak_dapat_diakses. Isi status_cek & dicek_pada; bila berubah dari dapat_diakses → tidak_dapat_diakses, dispatch event
   TautanBerkasTidakDapatDiakses (notifikasi di F7.1). Hasil hanya informatif.
7. TautanBerkasObserver: created/updated (url berubah) → reset status_cek=belum, dispatch PeriksaTautanBerkas; bila jenis
   foto_penyerahan atau poster → Cache::increment('galeri:versi') / increment('katalog:versi').
8. Rate limiter 'simpan-tautan' 60/jam/user.
9. App\Http\Controllers\BukaTautanController GET /tautan/{tautanBerkas}/buka (auth, throttle 'buka-tautan' 120/jam): authorize
   'view' lewat TautanBerkasPolicy (buat sekarang dengan aturan dasar: delegasikan ke policy pemilik — Gate::allows('view', $tautan->pemilik));
   catat activitylog (log 'tautan', event 'dibuka', properti id & jenis, TANPA menyimpan isi); redirect()->away($url).
10. Komponen Blade <x-tautan-berkas :tautan="$t" /> : tombol "Buka berkas" (target _blank, rel noopener noreferrer, href route tautan.buka),
    badge status cek, label penyedia; dan <x-pratinjau-drive :tautan="$t" /> berupa iframe urlPratinjau() dengan placeholder bila null.
11. Karena model Prestasi/Kompetisi baru dibuat di F4/F6, test langkah ini memakai model pemilik uji di tests/Fixtures (tabel dibuat
    di test) yang memiliki policy view sederhana.
    Test Pest: http:// ditolak; bit.ly ditolak; domain example.com ditolak; folder Drive ditolak; tautan Drive /file/d/ABC/view?usp=sharing
    → drive_file_id ABC, url_normal tanpa usp; *.unsil.ac.id diterima; PeriksaTautanBerkas dengan Http::fake (200 → dapat_diakses,
    302 ke accounts.google.com → tidak_dapat_diakses, timeout → tidak_dapat_diakses) dan tidak memblokir penyimpanan; perubahan url
    tercatat old→new di activity_log; route tautan.buka mencatat log dan redirect 302; mode selain tautan melempar exception;
    test arsitektur: tidak ada FileUpload/UploadedFile di app/ kecuali Pages Impor*.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Tabel `tautan_berkas` ada dengan kolom & ENUM sesuai standar.
- `TautanBerkasValid` menolak `http://`, pemendek URL, domain asing, dan tautan folder.
- Job `PeriksaTautanBerkas` tampil di Horizon antrean `berkas` dan tidak mengunduh isi berkas.
- Test arsitektur "tanpa unggahan selain impor" hijau.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(bukti): tambah fondasi tautan berkas tanpa unggah ke server"
```

### F3.8 — Login Google akun kampus (opsional) dan penautan akun
**Tujuan:** Mahasiswa/dosen masuk dengan akun Google kampus yang sudah ada di master; akun lokal tetap tersedia.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-20.
1. composer require laravel/socialite. config/services.php 'google' dari env GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI.
2. App\Http\Controllers\GoogleAuthController: redirect() (scopes openid email profile, parameter hd tidak dijadikan satu-satunya
   pengaman) dan callback(). Route /auth/google/redirect dan /auth/google/callback, middleware guest + throttle:google-callback
   (10/menit/IP). Aktif hanya bila config('puspresma.google_login') true; jika tidak, 404.
3. Logika callback (dalam Action App\Actions\Auth\MasukDenganGoogle):
   a. Tolak bila email tidak terverifikasi Google atau domain bukan config('puspresma.domain_mahasiswa') / domain_pegawai.
   b. Domain mahasiswa: cari Mahasiswa by email; bila tidak ada, cari by npm = bagian lokal email (pola perlu verifikasi; buat
      konfigurable lewat closure/konfigurasi). Bila tidak ditemukan → tolak "Data Anda belum terdaftar, hubungi admin prodi".
      Bila ditemukan: buat/ambil User (email, name, npm, prodi_id, google_id), assign peran mahasiswa, tautkan mahasiswa.user_id.
   c. Domain pegawai: cari User by email (staf yang dibuat super-admin) atau Dosen by email → buat User peran dosen.
      Bila tidak ada → tolak.
   d. Status mahasiswa selain aktif/cuti → tolak login portal.
   e. Login, catat activitylog 'auth', arahkan ke panel sesuai peran.
4. Tambahkan tombol "Masuk dengan Google" di halaman login kedua panel (render hook Filament) hanya bila fitur aktif.
5. Test Pest dengan Socialite::fake/mock: email gmail ditolak; mahasiswa terdaftar masuk dan mendapat peran mahasiswa;
   email kampus yang tidak ada di master ditolak; mahasiswa status lulus ditolak; fitur nonaktif → 404.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Dengan `GOOGLE_LOGIN_ENABLED=true` dan kredensial uji, akun mahasiswa terdaftar dapat masuk ke `/portal`.
- Akun `@gmail.com` ditolak dengan pesan berbahasa Indonesia.
- Dengan `GOOGLE_LOGIN_ENABLED=false`, tombol tidak tampil dan route 404.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(auth): tambah login google akun kampus dan penautan master"
```

### F3.9 — Tutup fase F3
**Tujuan:** PR, merge, tag `v0.3.0`.

**Prompt (salin ke agen AI):**
```text
Tutup fase F3: jalankan pint --test, phpstan, test (perbaiki tanpa fitur baru). Pastikan semua migrasi fase ini dapat
di-rollback (php artisan migrate:rollback --step=N lalu migrate kembali di database test). Perbarui CHANGELOG.md [0.3.0]
(master prodi/mahasiswa/dosen, master klasifikasi prestasi, impor Excel, pengaturan, bobot poin, fondasi tautan berkas, login Google).
Cetak deskripsi PR sesuai templat.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- CI hijau; reviewer berhasil mengimpor contoh mahasiswa dan login sebagai mahasiswa.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "docs(changelog): catat fase 3 data master dan impor"
git push -u origin feat/f3-master-data
gh pr create --base main --title "Fase 3: data master, impor, tautan berkas & login Google" --body-file pr-f3.md
gh pr merge --squash --subject "feat(master): fase 3 data master impor dan login google" --delete-branch
git switch main && git pull
git tag -a v0.3.0 -m "Fase 3: data master, impor, tautan berkas & login Google"
git push origin main --tags
```

---

## F4 — Pengajuan prestasi & bukti

### F4.1 — Tabel prestasi, anggota, riwayat status, relasi tautan bukti
**Branch:** `feat/f4-pengajuan-prestasi`
**Tujuan:** Struktur data inti prestasi beserta enum dan model.

**Prompt (salin ke agen AI):**
```text
Ikuti 03-SKEMA-DATABASE.md bagian 2 dan 3.11–3.15 persis (tipe, null, default, indeks, FK, FULLTEXT).
1. Enum: StatusPrestasi (dengan label(), warna() untuk badge Filament, dan method bolehDiubahMahasiswa(): true untuk draf &
   perlu_perbaikan), StatusKonfirmasiPembimbing, BentukPartisipasi, BidangPrestasi, ModaKegiatan, PeranAnggota. (JenisTautan sudah dibuat di F3.7.)
2. Migrasi: create_prestasi_table (tanpa FK kompetisi_id dulu — kolom nullable biasa; FK ditambahkan di F6.1),
   create_prestasi_anggota_table, create_prestasi_anggota_eksternal_table,
   create_riwayat_status_prestasi_table. TIDAK ada tabel berkas bukti; bukti memakai tautan_berkas (F3.7). FULLTEXT index prestasi(nama_kegiatan, penyelenggara).
3. Model Prestasi: HasUuids (primary key UUIDv7), SoftDeletes, LogsActivity
   (logOnlyDirty, kecuali kolom teknis), cast enum & date. Relasi: pengaju (Mahasiswa), prodi, pembuat (User dibuat_oleh), jenisKegiatan,
   kategoriLomba, tingkat, capaian, dosenPembimbing, anggota (hasMany PrestasiAnggota), mahasiswa (belongsToMany via prestasi_anggota),
   anggotaEksternal, tautanBukti (morphMany TautanBerkas, name 'pemilik', jenis selain poster), riwayatStatus, verifikator.
   Pastikan alias morph 'prestasi' terdaftar di enforceMorphMap. Hook saving: isi tahun dari tanggal_selesai dan nama_kegiatan_normal via
   App\Services\NormalisasiTeks::kegiatan() (lowercase, hilangkan tanda baca, angka tahun 4 digit, kata umum: "lomba", "kompetisi",
   "tingkat", "nasional", "internasional", "tahun", rapikan spasi). Scope: status(StatusPrestasi ...), terverifikasi(),
   melibatkanProdi(int $prodiId) (whereHas anggota.prodi_id), milikMahasiswa(int $mahasiswaId) (whereHas anggota).
4. Model PrestasiAnggota (cast poin decimal:2, setuju_publikasi bool), PrestasiAnggotaEksternal, RiwayatStatusPrestasi (const UPDATED_AT = null).
5. Factory: PrestasiFactory dengan state draf(), diajukan(), terverifikasi(), kelompok(), internasional(); PrestasiAnggotaFactory;
   TautanBerkasFactory state untukPrestasi(), sertifikat(), fotoPublik() (Drive, status_cek dapat_diakses).
6. Test Pest: nama_kegiatan_normal "Lomba Debat Nasional 2026!" menjadi "debat"; tahun terisi; scope melibatkanProdi menemukan
   prestasi dengan anggota lintas prodi; id terisi otomatis.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Empat tabel baru ada dengan indeks sesuai dokumen (`SHOW INDEX FROM prestasi`); tidak ada tabel penyimpan berkas.
- Test normalisasi dan scope hijau.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(db): tambah tabel prestasi anggota dan riwayat status"
```

### F4.2 — Formulir pengajuan prestasi (portal mahasiswa)
**Tujuan:** Wizard pengisian data kegiatan, capaian, dan tingkat dengan validasi kondisional.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-03, BR-04 (bagian kolom), BR-05, BR-22, BR-14.
Di panel portal buat App\Filament\Portal\Resources\PrestasiSayaResource (model Prestasi, label "Prestasi Saya"):
1. Query: hanya prestasi di mana mahasiswa login adalah anggota (scope milikMahasiswa). Tabel: nomor registrasi, nama kegiatan,
   tingkat, capaian, tanggal selesai, status (badge), peran saya (pengaju/anggota). Filter status & tahun.
2. Form wizard 4 langkah (langkah 3 & 4 diisi di F4.3 & F4.4; sekarang buat placeholder):
   Langkah 1 "Kegiatan": jenis_kegiatan_id (select dari OpsiMaster, live), bidang (radio akademik/non-akademik), kategori_lomba_id
   (bergantung jenis), nama_kegiatan (wajib, maks 255), judul_karya (opsional), penyelenggara (wajib), url_kegiatan (url, opsional),
   tanggal_mulai, tanggal_selesai (after_or_equal mulai, before_or_equal today), moda (radio), lokasi (wajib bila luring/hibrida),
   negara_penyelenggara (default Indonesia), deskripsi (textarea polos, maks 2000).
   Langkah 2 "Capaian & Tingkat": tingkat_id (live), capaian_id (opsi bergantung jenis kegiatan), capaian_keterangan, bentuk
   (individu/kelompok), jumlah_peserta, jumlah_pt_peserta (wajib & tampil bila tingkat nasional), jumlah_negara_peserta &
   negara_peserta (wajib & tampil bila internasional; helper text menyebut ambang dari Pengaturan ambang_negara_internasional),
   nomor_sertifikat.
   Peringatan (bukan blokir) BR-22: tampilkan Placeholder/Callout bila tanggal_selesai lebih tua dari batas_klaim_bulan atau tahun
   kegiatan < angkatan mahasiswa.
3. Saat create: pengaju_id = mahasiswa login, prodi_id = prodi mahasiswa, dibuat_oleh = user, status = draf; dalam transaksi
   buat PrestasiAnggota (is_pengaju true, peran ketua, prodi_id snapshot). Simpan riwayat status (null → draf).
4. PrestasiPolicy (buat sekarang, akan diperluas di F5.1): viewAny portal = prestasi.buat-milik; view = anggota prestasi;
   create = prestasi.buat-milik dan user memiliki relasi mahasiswa; update = pengaju && status->bolehDiubahMahasiswa();
   delete = pengaju && status draf. Pasang di Resource. Tombol edit/hapus tersembunyi bila tidak diizinkan.
5. Validasi diletakkan di App\Http\Requests\ atau rules form, dan juga dalam Action App\Actions\Prestasi\SimpanPrestasi
   (dipakai juga oleh admin di F5.3) agar tidak bergantung pada UI.
6. Test Pest: mahasiswa membuat draf dengan data valid; tanggal_selesai di masa depan ditolak; tingkat internasional tanpa
   jumlah_negara_peserta ditolak; capaian rekognisi tidak bisa dipilih untuk jenis kompetisi; mahasiswa A tidak dapat melihat/ubah
   draf mahasiswa B (403); draf dapat dihapus, prestasi diajukan tidak.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Mahasiswa dapat menyimpan draf prestasi dari ponsel (uji responsif di DevTools 375 px).
- Kolom internasional muncul hanya saat tingkat internasional dipilih.
- Mahasiswa lain mendapat 403 saat membuka URL draf tersebut.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(prestasi): tambah formulir pengajuan prestasi mahasiswa"
```

### F4.3 — Anggota tim lintas prodi, anggota eksternal, dosen pembimbing
**Tujuan:** Langkah 3 wizard: anggota FKIP (kredit penuh), anggota luar FKIP, dan pembimbing.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-01, BR-02, BR-04 (jumlah anggota), BR-11 (pengisian pembimbing).
1. Langkah 3 wizard "Tim & Pembimbing" di PrestasiSayaResource:
   - Tampil hanya bila bentuk = kelompok: Repeater "Anggota FKIP" (select mahasiswa_id dengan pencarian server-side by npm/nama,
     minimal 2 karakter, hanya status aktif/cuti, menampilkan "NPM — Nama (Prodi)"; field peran ketua/anggota). Pengaju tidak muncul
     di opsi (sudah otomatis anggota). Tidak boleh mahasiswa ganda. Hanya satu ketua.
   - Repeater "Anggota luar FKIP": nama, nomor_induk, institusi, program_studi.
   - Dosen pembimbing: select dosen_pembimbing_id (pencarian nidn/nama) ATAU teks nama_pembimbing_lain (bila pembimbing tidak ada di
     master; helper: "pembimbing di luar daftar tidak dapat dikonfirmasi lewat sistem").
2. Action App\Actions\Prestasi\SimpanAnggotaTim: sinkronisasi prestasi_anggota (pertahankan baris pengaju dan nilai setuju_publikasi
   anggota yang sudah ada), isi prodi_id snapshot dari mahasiswa.prodi_id, sinkronisasi anggota eksternal. Validasi: bentuk individu
   → hanya pengaju, tanpa eksternal; bentuk kelompok → total anggota (internal + eksternal) >= 2.
   Bila bentuk diubah dari kelompok ke individu, hapus anggota selain pengaju (konfirmasi di UI).
3. Saat anggota baru ditambahkan, siapkan event App\Events\AnggotaTimDitambahkan (listener notifikasi dibuat di F7.1; untuk sekarang
   cukup event terdispatch).
4. Anggota non-pengaju dapat melihat prestasi di daftar "Prestasi Saya" (read-only) — pastikan policy view mengizinkan anggota.
5. Test Pest: tim dengan anggota dari prodi lain tersimpan dengan prodi_id snapshot benar; mahasiswa ganda ditolak; kelompok dengan
   1 anggota ditolak; individu dengan anggota tambahan ditolak; anggota non-pengaju dapat view tetapi tidak dapat update;
   event AnggotaTimDitambahkan terdispatch.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Pengaju menambahkan anggota dari prodi lain; anggota tersebut melihat prestasi di portalnya.
- Dosen pembimbing dapat dipilih lewat pencarian NIDN/nama.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(prestasi): kelola anggota tim lintas prodi dan dosen pembimbing"
```

### F4.4 — Tautan bukti prestasi
**Tujuan:** Langkah 4 wizard: tautan sertifikat/piagam, foto penyerahan, surat tugas lewat `tautan_berkas`, dengan petunjuk privasi berbagi.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-06, BR-14, BR-16 (tampil_publik hanya untuk foto), BR-31. Gunakan fondasi F3.7 (PenyimpananBerkas, TautanBerkasValid,
PeriksaTautanBerkas). JANGAN memakai FileUpload atau menyimpan berkas apa pun di server.
1. Action App\Actions\Bukti\SimpanTautanBukti(Prestasi $p, JenisTautan $jenis, string $url, ?string $label, bool $tampilPublik, User $u):
   jenis hanya sertifikat_piagam/foto_penyerahan/surat_tugas/lainnya; tolak bila status prestasi tidak bolehDiubahMahasiswa() (kecuali
   admin-fakultas); maksimal 10 tautan per prestasi; tampil_publik hanya disimpan untuk foto_penyerahan; memanggil
   PenyimpananBerkas::simpanTautan. Action HapusTautanBukti (soft delete, syarat status sama) dan UbahTautanBukti (URL lama → baru di
   activitylog lewat observer).
2. Langkah 4 wizard "Tautan Bukti & Publikasi" di PrestasiSayaResource: Repeater tautan (select jenis, TextInput url dengan rule
   TautanBerkasValid, label/keterangan, toggle tampil_publik yang hanya tampil untuk foto_penyerahan dengan teks "Izinkan foto ini tampil
   di galeri publik bila prestasi terverifikasi; berkas di Drive harus dibagikan 'Siapa saja yang memiliki link'"). Callout petunjuk
   berbagi (BR-31): sertifikat/piagam & foto boleh publik-dengan-link; surat tugas atau dokumen ber-NIK/NIP/nilai bagikan terbatas ke
   akun/organisasi kampus; gunakan "Salin link" di aplikasi Drive ponsel; pastikan foto tanpa data lokasi. Setiap tautan menampilkan badge
   status cek (belum/dapat diakses/tidak dapat diakses) — "tidak dapat diakses" hanya peringatan: "verifikator mungkin tidak bisa membuka;
   periksa pengaturan berbagi". Rate limiter 'simpan-tautan'.
3. TautanBerkasPolicy (lengkapi dari F3.7) untuk pemilik prestasi: view — anggota prestasi, dosen pembimbing, admin-fakultas/pimpinan,
   admin-prodi bila prestasi melibatkan prodinya; create/update/delete — pengaju (atau admin-fakultas) dan status bolehDiubahMahasiswa().
   Tautan TIDAK pernah dirender ke peran yang tidak berhak; otorisasi di Policy/Resource dan route tautan.buka, bukan di Blade.
4. Di halaman view prestasi portal: daftar tautan dengan <x-tautan-berkas> dan pratinjau Drive untuk sertifikat.
5. Test Pest: tautan http ditolak; tautan folder ditolak; drive_file_id tersimpan; tampil_publik pada sertifikat diabaikan (false);
   tautan ke-11 ditolak; tambah tautan saat status terverifikasi ditolak; mahasiswa lain 403 pada route tautan.buka (IDOR via id);
   admin-prodi prodi lain 403; admin-prodi prodi anggota 302 redirect; perubahan URL tercatat old→new; tidak ada berkas baru di storage
   setelah seluruh test (assert Storage::disk('local')->allFiles() kosong untuk folder selain tmp).

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Mahasiswa dapat menempel tautan Drive dari ponsel dan melihat status keteraksesannya.
- Tidak ada komponen unggah berkas di wizard; `storage/app` tidak bertambah setelah pengajuan.
- Mahasiswa lain mendapat 403 saat membuka `/tautan/{id}/buka` milik orang lain.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(bukti): tambah tautan bukti prestasi dengan petunjuk privasi"
```

### F4.5 — Aksi Ajukan dan Tarik Kembali
**Tujuan:** Mengirim prestasi ke verifikasi dengan pemeriksaan kelengkapan, nomor registrasi, dan riwayat status.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-04, BR-07, BR-11 (status konfirmasi menjadi menunggu), BR-03.
1. Exception App\Exceptions\TransisiTidakSah dan App\Exceptions\PrestasiTidakLengkap (membawa daftar pesan).
2. Trait/kelas dasar App\Actions\Prestasi\TransisiStatus dengan method ubah(Prestasi $p, StatusPrestasi $ke, User $u, ?string $catatan):
   ambil Cache::lock("lock:prestasi:{$p->id}:status", 10)->block(5), DB::transaction, muat ulang prestasi dengan lockForUpdate(),
   cek transisi sah dari tabel transisi BR-07 (simpan sebagai method StatusPrestasi::bolehKe(StatusPrestasi $ke): bool),
   update status, tulis RiwayatStatusPrestasi, activitylog, lalu dispatch event App\Events\StatusPrestasiBerubah($p, $dari, $ke).
3. App\Actions\Prestasi\AjukanPrestasi: hanya pengaju (atau admin-fakultas); status draf atau perlu_perbaikan; periksa kelengkapan BR-04:
   kolom wajib, tanggal, minimal satu tautan sertifikat_piagam (tautan_berkas), jumlah anggota sesuai bentuk, capaian cocok jenis, kolom wajib tingkat.
   Bila belum punya nomor_registrasi: buat "PRS-{tahun}-{urut 5 digit}" dengan Cache::lock("lock:nomor-registrasi:{tahun}") +
   SELECT MAX dalam transaksi. Set diajukan_at. Bila dosen_pembimbing_id terisi dan status konfirmasi bukan dikonfirmasi
   untuk dosen yang sama → menunggu. Transisi ke diajukan. Rate limiter 'ajukan-prestasi' 10/jam/user.
   (Deteksi duplikat dipanggil di F5.2 lewat listener event; jangan buat sekarang.)
4. App\Actions\Prestasi\TarikKembaliPrestasi: hanya pengaju, hanya dari diajukan dan belum ada riwayat aksi verifikator
   (lolos_prodi) → draf.
5. Di PrestasiSayaResource: aksi "Ajukan" (modal konfirmasi berisi ringkasan + daftar kekurangan bila tidak lengkap) dan
   "Tarik Kembali"; tampilkan timeline riwayat status beserta catatan verifikator di halaman view.
6. Test Pest: ajukan tanpa tautan sertifikat ditolak dengan pesan; ajukan lengkap → status diajukan, nomor PRS-2026-00001 lalu 00002;
   dua ajukan paralel (simulasi) tidak menghasilkan nomor ganda; ajukan ulang dari perlu_perbaikan mempertahankan nomor;
   anggota non-pengaju tidak dapat mengajukan; transisi draf→terverifikasi melempar TransisiTidakSah; riwayat status tercatat.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Tombol Ajukan menampilkan daftar kekurangan bila belum lengkap.
- Setelah diajukan, form menjadi read-only dan nomor registrasi tampil.
- Riwayat status tampil di halaman detail.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(verifikasi): tambah aksi ajukan dan tarik kembali prestasi"
```

### F4.6 — Tutup fase F4
**Tujuan:** PR, merge, tag `v0.4.0`.

**Prompt (salin ke agen AI):**
```text
Tutup fase F4: jalankan pint --test, phpstan, test. Tambahkan test end-to-end tests/Feature/Alur/PengajuanPrestasiTest.php:
mahasiswa membuat prestasi kelompok lintas prodi, menambahkan tautan sertifikat Drive, mengajukan, lalu anggota lain melihatnya read-only.
Periksa N+1 di tabel Prestasi Saya (tidak ada LazyLoadingViolation dengan 30 data). Perbarui CHANGELOG.md [0.4.0]. Cetak deskripsi PR.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Test alur end-to-end hijau; CI hijau.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "test(prestasi): uji alur pengajuan prestasi end-to-end"
git push -u origin feat/f4-pengajuan-prestasi
gh pr create --base main --title "Fase 4: pengajuan prestasi & bukti" --body-file pr-f4.md
gh pr merge --squash --subject "feat(prestasi): fase 4 pengajuan prestasi dan bukti" --delete-branch
git switch main && git pull
git tag -a v0.4.0 -m "Fase 4: pengajuan prestasi & bukti"
git push origin main --tags
```

---

## F5 — Verifikasi, duplikat, pembimbing & poin

### F5.1 — Action transisi verifikasi dan PrestasiPolicy lengkap
**Branch:** `feat/f5-verifikasi`
**Tujuan:** Seluruh aksi verifikator sebagai Action yang teruji, termasuk tahap prodi opsional dan buka verifikasi.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-07, BR-08, BR-09, BR-10 (cek blokir), BR-11, BR-12, BR-13, BR-15, BR-28, BR-29. Gunakan App\Actions\Prestasi\TransisiStatus dari F4.5.
Buat Action:
1. LoloskanTahapProdi(Prestasi, User, ?catatan): hanya bila Pengaturan verifikasi_prodi_aktif true; user punya prestasi.verifikasi-prodi
   dan user.prodi_id = prestasi.prodi_id; diajukan → lolos_prodi.
2. KembalikanUntukPerbaikan(Prestasi, User, catatan): catatan wajib min 10 karakter; dari diajukan/lolos_prodi; admin-fakultas
   atau admin-prodi (prodi pengaju, hanya bila tahap prodi aktif dan status diajukan). Simpan catatan_verifikasi.
3. TolakPrestasi(Prestasi, User, catatan): sama syarat catatan; → ditolak (final).
4. VerifikasiPrestasi(Prestasi, User, ?catatan, bool $ambilAlih = false, bool $tanpaKonfirmasiPembimbing = false):
   hanya prestasi.verifikasi (admin-fakultas). Bila tahap prodi aktif dan status diajukan → wajib $ambilAlih + catatan.
   Blokir bila Prestasi::adaKandidatDuplikatTerbuka() bernilai true. Tabel kandidat_duplikat baru dibuat di F5.2, jadi pada langkah
   ini buat method tersebut mengembalikan false dengan komentar "TODO F5.2" agar langkah tetap mandiri.
   Pembimbing: menunggu → wajib $tanpaKonfirmasiPembimbing + catatan; disangkal → tolak dengan pesan.
   Wajib parameter bool $sudahMembukaTautan = true (BR-29; tolak bila false). Set diverifikasi_at, diverifikasi_oleh,
   verifikasi_tanpa_konfirmasi; → terverifikasi. Di dalam transaksi yang sama panggil App\Actions\Bukti\SimpanSnapshotTautan:
   migrasi create_snapshot_tautan_verifikasi_table (03-SKEMA 3.14a) dan salin setiap tautan bukti aktif (url, drive_file_id, jenis,
   status_cek, alasan 'verifikasi', dibuka_verifikator = ada activitylog 'tautan'/'dibuka' oleh verifikator untuk tautan itu).
   Setelah commit transaksi: naikkan Cache::increment('statistik:versi') dan Cache::increment('galeri:versi').
5. BukaVerifikasiPrestasi(Prestasi, User, catatan): prestasi.buka-verifikasi; terverifikasi → perlu_perbaikan; kosongkan poin anggota
   (poin = null); naikkan versi cache statistik & galeri.
6. Lengkapi App\Policies\PrestasiPolicy untuk panel admin: viewAny (lihat-semua | lihat-prodi), view (lihat-semua | prodi terlibat via
   anggota | pembimbing | anggota), update oleh admin-fakultas hanya bila status bolehDiubahMahasiswa() (BR-12), ability terpisah:
   loloskanProdi, kembalikan, tolak, verifikasi, bukaVerifikasi — masing-masing memeriksa permission + cakupan prodi + status saat ini.
   Super-admin TIDAK memiliki ability verifikasi (lihat F2.2).
7. Test Pest (tests/Feature/Verifikasi/*): setiap transisi sah berhasil dan tercatat di riwayat; perlu_perbaikan tanpa catatan ditolak;
   admin-prodi tidak dapat verifikasi final; admin-prodi prodi lain tidak dapat loloskan; tahap prodi nonaktif → loloskan ditolak;
   verifikasi dari diajukan saat tahap prodi aktif tanpa ambil alih ditolak; pembimbing disangkal memblokir; ditolak tidak dapat
   diubah; buka verifikasi menghapus poin tetapi tidak
   menghapus snapshot; verifikasi kedua menambah snapshot baru; snapshot tidak berubah walau tautan diubah kemudian; verifikasi tanpa
   $sudahMembukaTautan ditolak; super-admin ditolak verifikasi; versi cache naik.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Semua test transisi hijau (minimal 12 kasus).
- Tidak ada perubahan `status` prestasi di luar `app/Actions/Prestasi` (cek `grep -rn "->status =" app/`).

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(verifikasi): tambah action transisi verifikasi dan policy prestasi"
```

### F5.2 — Deteksi duplikat
**Tujuan:** Kandidat duplikat otomatis saat diajukan dan peninjauan oleh admin.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-10.
1. Enum StatusTinjauDuplikat, AlasanDuplikat. Migrasi create_kandidat_duplikat_table sesuai 03-SKEMA bagian 3.16. Model KandidatDuplikat
   (relasi prestasi, pembanding, peninjau), relasi Prestasi::kandidatDuplikat(). Ganti TODO F5.1: adaKandidatDuplikatTerbuka() memeriksa
   status belum_ditinjau.
2. App\Services\DeteksiDuplikatService::cari(Prestasi $p): Collection — 
   a. Kemiripan kegiatan: kandidat = prestasi lain (bukan dirinya, bukan draf, tidak soft-deleted) yang berbagi minimal satu mahasiswa_id
      anggota DAN |tanggal_mulai selisih| <= Pengaturan rentang_hari_duplikat; pra-saring dengan FULLTEXT MATCH(nama_kegiatan,
      penyelenggara) AGAINST(...) bila daftar besar; skor = similar_text pada nama_kegiatan_normal dalam persen; kandidat bila
      skor/100 >= ambang_kemiripan_duplikat.
   b. Tautan sama: tautan bukti dengan drive_file_id sama (atau url_normal sama untuk penyedia non-Drive) pada prestasi lain (bukan
      draf) → skor 100, alasan tautan_sama.
   Simpan dengan upsert (unique prestasi_id, pembanding_id, alasan); jangan menimpa status_tinjau yang sudah ditinjau.
3. Job App\Jobs\DeteksiDuplikatPrestasi (antrean default, unique per prestasi_id selama 60 detik) dipicu listener saat
   StatusPrestasiBerubah ke diajukan. Bila menemukan kandidat baru → event KandidatDuplikatDitemukan (notifikasi di F7.1).
4. Action App\Actions\Duplikat\TinjauKandidatDuplikat(KandidatDuplikat, User, StatusTinjauDuplikat, ?catatan): permission duplikat.tinjau;
   status duplikat wajib catatan. Policy KandidatDuplikatPolicy.
5. Test Pest: dua prestasi tim yang sama (anggota sama, "Lomba Debat Nasional 2026" vs "Debat Nasional 2026", tanggal selisih 1 hari)
   → kandidat terbentuk; nama berbeda jauh → tidak; anggota berbeda → tidak; tautan sertifikat dengan drive_file_id sama
   (walau URL berbeda parameter, mis. /view vs /preview) → kandidat tautan_sama;
   verifikasi diblokir sampai ditinjau bukan_duplikat; job dijalankan dua kali tidak menggandakan baris.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Mengajukan dua prestasi tim yang sama menghasilkan baris `kandidat_duplikat`.
- Verifikasi prestasi tersebut ditolak sampai kandidat ditinjau.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(duplikat): deteksi kandidat duplikat prestasi saat diajukan"
```

### F5.3 — Antrean verifikasi di panel admin
**Tujuan:** Admin fakultas/prodi memverifikasi dengan pratinjau bukti, kandidat duplikat, dan input atas nama mahasiswa.

**Prompt (salin ke agen AI):**
```text
Buat App\Filament\Admin\Resources\PrestasiResource (grup Prestasi):
1. Query di-scope: admin-fakultas/pimpinan semua; admin-prodi melibatkanProdi(user.prodi_id) (BR-15). Eager load relasi untuk tabel.
2. Tab: "Antrean" (diajukan + lolos_prodi; untuk admin-prodi dengan tahap aktif: diajukan prodinya), "Perlu Perbaikan", "Terverifikasi",
   "Ditolak", "Semua". Badge jumlah pada tab Antrean.
3. Tabel: nomor registrasi, nama kegiatan, pengaju (npm-nama), prodi, tingkat, capaian, bentuk (ikon), tanggal selesai, status (badge),
   ikon peringatan bila: ada kandidat duplikat terbuka, tautan bukti tidak dapat diakses, pembimbing menunggu/disangkal, internasional dengan negara < ambang,
   nasional dengan PT < ambang. Filter prodi, tahun, jenis, tingkat, capaian, status, "ada duplikat". Pencarian FULLTEXT nama kegiatan/
   penyelenggara + npm/nama anggota.
4. Halaman View (infolist) dua kolom: kiri data prestasi, anggota internal (prodi masing-masing), anggota eksternal, pembimbing &
   status konfirmasi, peringatan validasi; kanan pratinjau tautan bukti dengan tab per tautan: iframe sematan Drive
   (https://drive.google.com/file/d/<id>/preview) bila penyedia Drive, selain itu placeholder; di atasnya tombol "Buka berkas" (route
   tautan.buka, tab baru), badge status cek, dan tanda centang "sudah Anda buka" (dari activitylog). Tampilkan peringatan bila status_cek
   tidak_dapat_diakses ("mungkin dibagikan terbatas; buka dengan akun kampus").
   RelationManager tambahan: Snapshot Tautan (read-only).
   RelationManager: Riwayat Status (read-only), Kandidat Duplikat (tautan ke prestasi pembanding, aksi Tinjau).
5. Aksi header di View (tampil sesuai Policy): "Lolos Tahap Prodi", "Kembalikan untuk Perbaikan" (textarea catatan wajib),
   "Tolak" (catatan wajib, konfirmasi ganda), "Verifikasi" (checkbox wajib "Saya telah membuka dan memeriksa semua tautan bukti"; peringatan bila ada tautan
   sertifikat_piagam yang belum Anda buka; checkbox ambil alih & verifikasi tanpa konfirmasi pembimbing tampil bila relevan, catatan
   opsional/wajib sesuai kondisi), "Buka Verifikasi", dan pada prestasi terverifikasi "Ganti tautan" (admin-fakultas; Action
   GantiTautanTerverifikasi: catatan wajib, URL lama → baru di activitylog, tambah snapshot alasan 'penggantian'; BR-30). Setiap aksi memanggil Action F5.1 dan menangkap
   TransisiTidakSah menjadi notifikasi galat Filament. Aksi massal tidak disediakan untuk verifikasi (harus per record).
6. Create/Edit oleh admin-fakultas (prestasi.buat-atas-nama): form yang sama dengan portal (ekstrak skema form ke
   App\Filament\Shared\PrestasiForm agar dipakai kedua panel), ditambah pilih pengaju (mahasiswa). Status awal draf; admin dapat
   langsung Ajukan lalu Verifikasi (tetap melalui transisi).
7. App\Filament\Admin\Resources\KandidatDuplikatResource: daftar global belum_ditinjau, aksi Tinjau.
8. Test Pest (Livewire Filament): admin-prodi tidak melihat prestasi yang tidak melibatkan prodinya; tombol Verifikasi tidak terlihat oleh
   admin-prodi; admin-fakultas memverifikasi via aksi dan status berubah serta snapshot tersimpan;
   verifikasi tanpa centang membuka tautan gagal validasi; Ganti tautan tanpa catatan gagal; aksi Kembalikan tanpa catatan gagal validasi; pimpinan hanya
   melihat (tanpa aksi); admin-fakultas membuat prestasi atas nama mahasiswa dan pengaju menjadi anggota.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Admin fakultas dapat memverifikasi prestasi dalam ≤ 3 klik dari tab Antrean dengan pratinjau sematan Drive di layar yang sama.
- Setelah verifikasi, relation manager Snapshot Tautan berisi URL bukti saat itu.
- Admin prodi hanya melihat prestasi yang melibatkan prodinya.
- Ikon peringatan duplikat/pembimbing tampil sesuai kondisi.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(verifikasi): tambah antrean verifikasi dan input atas nama di panel admin"
```

### F5.4 — Konfirmasi dosen pembimbing
**Tujuan:** Dosen mengonfirmasi atau menyangkal pembimbingan dari panel portal.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-11.
1. Action App\Actions\Pembimbing\KonfirmasiPembimbingan(Prestasi, User) dan SangkalPembimbingan(Prestasi, User, alasan min 10 karakter):
   user harus dosen dengan dosen.user_id = user.id dan dosen.id = prestasi.dosen_pembimbing_id; status konfirmasi harus menunggu;
   set dikonfirmasi_pembimbing_at / catatan_pembimbing; activitylog; event PembimbingMenjawab.
2. Saat mahasiswa mengganti dosen_pembimbing_id pada status perlu_perbaikan, status konfirmasi direset (dikonfirmasi_pembimbing_at null);
   saat menghapus pembimbing → tidak_ada. Implementasikan di SimpanPrestasi/observer dengan test.
3. Panel portal: App\Filament\Portal\Resources\KonfirmasiPembimbingResource (peran dosen): daftar prestasi dengan dosen_pembimbing_id =
   dosen login dan status konfirmasi menunggu; tampilan ringkas (kegiatan, mahasiswa, tingkat, capaian, tombol Buka berkas sertifikat via route tautan.buka);
   aksi Konfirmasi dan Sangkal (modal alasan). PrestasiBimbinganResource: daftar yang dikonfirmasi (read-only).
   Badge navigasi jumlah menunggu.
4. Policy: dosen hanya melihat prestasi bimbingannya; tidak dapat melihat kontak mahasiswa.
5. Test Pest: dosen yang ditunjuk dapat konfirmasi; dosen lain 403; sangkal tanpa alasan ditolak; mengganti pembimbing mereset status;
   verifikasi admin (F5.1) diblokir saat disangkal dan diizinkan setelah dikonfirmasi.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Dosen melihat badge permintaan konfirmasi di `/portal` dan dapat menjawabnya.
- Status konfirmasi tampil di halaman verifikasi admin.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(pembimbing): tambah konfirmasi dan penyangkalan dosen pembimbing"
```

### F5.5 — Poin prestasi: snapshot, rekap, dan hitung ulang
**Tujuan:** Menghitung poin saat terverifikasi (bila diaktifkan) dan menampilkan rekap.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-18, BR-12.
1. App\Actions\Poin\HitungPoinPrestasi(Prestasi): bila Pengaturan poin_aktif false → tidak melakukan apa pun. Ambil bobot via
   PenghitungPoin::bobot(jenis, tingkat, capaian, tanggal_selesai); bila null → poin 0.00 dan catat peringatan di activitylog.
   Mode penuh: setiap anggota internal = bobot; mode dibagi: bobot / jumlah anggota internal dengan bcdiv skala 2. Simpan ke
   prestasi_anggota.poin. Gunakan aritmetika string/bcmath, jangan float.
2. Listener: saat StatusPrestasiBerubah ke terverifikasi → dispatch job (antrean default) yang memanggil HitungPoinPrestasi.
3. App\Actions\Poin\HitungUlangPoinMassal(User, ?int $tahun): permission poin.hitung-ulang; hitung ulang semua prestasi terverifikasi
   (chunk 200) sesuai bobot saat ini; catat ringkasan di activitylog. Tombol di Page MatriksBobotPoin dengan konfirmasi.
4. Portal: Page App\Filament\Portal\Pages\PoinSaya — total poin terverifikasi per tahun dan per tahun akademik (BR-21), tabel rincian.
   Sembunyikan menu bila poin_aktif false.
5. Admin: Page App\Filament\Admin\Pages\PeringkatPoin (grup Poin) — peringkat mahasiswa berdasarkan SUM(poin) dengan filter periode
   (tanggal), prodi (scope admin-prodi), angkatan; tampilkan jumlah prestasi dan poin. (Ekspor dibuat di F8.2.)
6. Test Pest: mode penuh 3 anggota → masing-masing 40.00; mode dibagi → 13.33; poin_aktif false → null; buka verifikasi →
   null; hitung ulang mengikuti bobot baru; peringkat admin-prodi hanya prodinya.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Dengan `poin_aktif = true` dan bobot terisi, verifikasi prestasi mengisi `prestasi_anggota.poin`.
- Mahasiswa melihat total poin di menu Poin Saya.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(poin): hitung snapshot poin saat verifikasi dan rekap peringkat"
```

### F5.6 — Tutup fase F5
**Tujuan:** PR, merge, tag `v0.5.0`.

**Prompt (salin ke agen AI):**
```text
Tutup fase F5: pint --test, phpstan, test. Tambahkan test alur tests/Feature/Alur/VerifikasiPrestasiTest.php: diajukan → kandidat duplikat (tautan Drive sama)
→ ditinjau bukan duplikat → pembimbing konfirmasi → (tahap prodi aktif) lolos prodi → verifikator membuka tautan → verifikasi →
snapshot tersimpan → poin terisi → buka verifikasi →
perlu perbaikan → ajukan ulang. Perbarui CHANGELOG.md [0.5.0]. Cetak deskripsi PR.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Test alur verifikasi hijau; CI hijau; reviewer mencoba verifikasi dengan akun admin-prodi dan admin-fakultas.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "test(verifikasi): uji alur verifikasi lengkap"
git push -u origin feat/f5-verifikasi
gh pr create --base main --title "Fase 5: verifikasi, duplikat, pembimbing & poin" --body-file pr-f5.md
gh pr merge --squash --subject "feat(verifikasi): fase 5 verifikasi duplikat pembimbing dan poin" --delete-branch
git switch main && git pull
git tag -a v0.5.0 -m "Fase 5: verifikasi, duplikat, pembimbing & poin"
git push origin main --tags
```

---

## F6 — Katalog kompetisi & galeri publik

### F6.1 — Katalog kompetisi (pengelolaan admin)
**Branch:** `feat/f6-katalog-galeri`
**Tujuan:** Admin mengumumkan kompetisi yang dapat diikuti beserta tenggat dan sasaran prodi.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-23.
1. composer require mews/purifier (atau stevebauman/purify bila belum mendukung Laravel 13); profil purifier 'deskripsi' mengizinkan
   p, br, strong, em, ul, ol, li, a[href|title] (rel nofollow noopener, target _blank), h3, h4, blockquote.
2. Enum StatusKompetisi. Migrasi create_kompetisi_table, create_kompetisi_prodi_table, create_kompetisi_minat_table (03-SKEMA 3.17–3.19),
   serta migrasi add_foreign_kompetisi_id_to_prestasi_table (FK SET NULL). Tambahkan alias morph 'kompetisi' dan relasi
   Kompetisi::poster() (morphOne TautanBerkas jenis poster). Model Kompetisi (HasUuids, slug otomatis unik dari
   judul, SoftDeletes, LogsActivity, mutator deskripsi → purifier), relasi prodiSasaran, minat, prestasi. Scope terbit(), masihDibuka().
   KompetisiObserver menaikkan Cache::increment('katalog:versi').
3. App\Filament\Admin\Resources\KompetisiResource (grup Katalog): form judul, penyelenggara, jenis kegiatan, bidang, kategori, tingkat,
   RichEditor deskripsi (toolbar terbatas), url_pendaftaran, url_panduan, tanggal_buka, tenggat_pendaftaran (datetime), tanggal
   pelaksanaan, moda, lokasi, biaya_pendaftaran (numeric, 2 desimal, prefix Rp), kontak_narahubung, tautan poster (TextInput dengan rule TautanBerkasValid, disimpan
   lewat PenyimpananBerkas ke tautan_berkas jenis poster; poster wajib dibagikan publik), prodi sasaran (multi-select; kosong = semua prodi). Aksi Terbitkan (Action TerbitkanKompetisi: tolak bila tenggat
   lewat), Arsipkan, Duplikat sebagai draf (untuk lomba tahunan).
4. KompetisiPolicy: kompetisi.kelola penuh; kompetisi.kelola-prodi (admin-prodi) hanya create/update/delete kompetisi yang prodi
   sasarannya tepat satu yaitu prodinya sendiri (paksa saat create).
5. Action ArsipkanKompetisiKedaluwarsa (dipakai scheduler F7.3): ubah terbit yang tenggatnya lewat menjadi diarsipkan.
6. Test Pest: deskripsi berisi <script> dibersihkan; terbitkan dengan tenggat lewat ditolak; admin-prodi tidak dapat menyasar prodi lain;
   arsip otomatis bekerja; biaya disimpan DECIMAL.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Admin fakultas menerbitkan kompetisi dengan tautan poster Drive dan sasaran prodi; tidak ada unggah gambar.
- Tag `<script>` di deskripsi hilang setelah disimpan.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(katalog): tambah pengelolaan katalog kompetisi"
```

### F6.2 — Katalog publik, minat mahasiswa, dan tautan ke pengajuan
**Tujuan:** Halaman `/kompetisi` publik, tombol "Berminat" di portal, dan prapengisian formulir prestasi dari katalog.

**Prompt (salin ke agen AI):**
```text
1. Livewire App\Livewire\Publik\KatalogKompetisi (route /kompetisi, nama kompetisi.index, middleware throttle:katalog-publik 60/menit/IP):
   kartu kompetisi terbit & masih dibuka, urut tenggat terdekat; filter jenis, tingkat, kategori, prodi; pencarian FULLTEXT;
   hasil di-cache dengan kunci "katalog:v{versi}:aktif:{md5(filter+halaman)}" 10 menit. Layout publik Tailwind 4, responsif.
   Badge "Tenggat 3 hari lagi" bila <= 3 hari.
2. App\Livewire\Publik\DetailKompetisi (route /kompetisi/{kompetisi:slug}): detail, poster dari <x-foto-drive> (lh3.googleusercontent.com/d/<id>, placeholder bila gagal) — komponen dibuat di
   F6.3, untuk langkah ini buat versi sederhana yang akan dipakai ulang —, deskripsi (sudah disanitasi; render dengan
   kelas prose), tombol "Daftar" (url_pendaftaran, rel noopener). Kompetisi draf/diarsipkan → 404 (diarsipkan boleh tampil dengan label
   "Pendaftaran ditutup" bila diakses langsung; pilih salah satu dan dokumentasikan).
3. Portal: Page App\Filament\Portal\Pages\KatalogKompetisi (mahasiswa & dosen) dengan daftar yang sama, tombol "Berminat"/"Batal minat"
   (mahasiswa saja) menyimpan kompetisi_minat; kompetisi sasaran prodi mahasiswa ditandai "Untuk prodi Anda".
4. Di PrestasiSayaResource langkah 1: field opsional "Dari katalog" (select kompetisi terbit/diarsipkan 2 tahun terakhir) yang mengisi
   nama_kegiatan, penyelenggara, jenis, kategori, tingkat, url_kegiatan; simpan kompetisi_id.
5. Beranda (/) menampilkan 4 kompetisi dengan tenggat terdekat.
6. Test Pest: kompetisi draf tidak tampil publik; filter tingkat bekerja; throttle mengembalikan 429 setelah 60 permintaan;
   mahasiswa dapat berminat sekali (unique); prapengisian dari katalog menyimpan kompetisi_id.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- `/kompetisi` dapat diakses tanpa login dan nyaman di ponsel.
- Mahasiswa menandai minat dan memilih kompetisi saat mengisi prestasi.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(katalog): tampilkan katalog publik dan minat mahasiswa"
```

### F6.3 — Persetujuan publikasi dan foto publik dari tautan Drive
**Tujuan:** Persetujuan opt-in per anggota (UU PDP) dan foto penyerahan publik yang ditampilkan langsung dari Google Drive.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-16, BR-17, BR-26, BR-31. Tidak ada pemrosesan atau penyimpanan gambar di server.
1. Action App\Actions\Galeri\UbahPersetujuanPublikasi(PrestasiAnggota, User, bool $setuju): hanya mahasiswa pemilik baris tersebut;
   set setuju_publikasi dan setuju_publikasi_at (null saat ditarik); activitylog (log 'persetujuan', properti: prestasi id, nilai,
   IP, user agent, versi teks persetujuan = hash md5 dari Pengaturan teks_persetujuan_publikasi); Cache::increment('galeri:versi').
2. Portal: Page App\Filament\Portal\Pages\PersetujuanPublikasi — daftar semua prestasi di mana mahasiswa menjadi anggota, menampilkan
   teks persetujuan dari Pengaturan, sakelar per prestasi, keterangan "status tampil di galeri" (Ya/Tidak dan alasannya: belum
   terverifikasi, pengaju belum setuju, disembunyikan admin, galeri nonaktif, foto belum diizinkan tampil, foto tidak dibagikan publik).
   Juga tampilkan sakelar yang sama di langkah 4 wizard.
3. Method Prestasi::tampilDiGaleri() (terverifikasi, galeri_aktif, pengaju setuju, tidak disembunyikan) dan Prestasi::fotoPublik():
   Collection tautan jenis foto_penyerahan dengan tampil_publik = true, penyedia google_drive, drive_file_id terisi, dan
   status_cek = dapat_diakses (artinya dibagikan publik). URL gambar = PenyimpananBerkas::urlGambar() →
   https://lh3.googleusercontent.com/d/<drive_file_id>. Komponen Blade <x-foto-drive :tautan="$t" :alt="$t->label" /> dengan
   loading="lazy", referrerpolicy="no-referrer", dan placeholder (onerror → gambar lokal resources/images/placeholder-prestasi.svg).
4. Bila status_cek foto berubah menjadi tidak_dapat_diakses atau mahasiswa mematikan tampil_publik, foto otomatis tidak dipakai
   (observer menaikkan galeri:versi).
5. Test Pest: anggota hanya dapat mengubah persetujuannya sendiri; penarikan menaikkan versi cache; fotoPublik() mengabaikan foto dengan
   status_cek tidak_dapat_diakses, tampil_publik false, atau penyedia non-Drive; tautan sertifikat tidak pernah masuk fotoPublik();
   URL gambar berbentuk https://lh3.googleusercontent.com/d/<id>; prestasi belum terverifikasi → tampilDiGaleri false.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Mahasiswa dapat memberi/menarik persetujuan dan melihat alasan prestasi/foto belum tampil.
- Foto galeri dimuat dari `lh3.googleusercontent.com`, bukan dari server aplikasi.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(galeri): tambah persetujuan publikasi dan foto publik dari tautan drive"
```

### F6.4 — Galeri prestasi publik dan moderasi
**Tujuan:** Halaman `/galeri` cepat (cache Redis), menghormati persetujuan, dan dapat dimoderasi admin.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-16, BR-17.
1. Livewire App\Livewire\Publik\GaleriPrestasi (route /galeri, throttle:galeri): kartu prestasi Prestasi::query()->tampilDiGaleri()
   (buat scope SQL yang setara dengan method F6.3), urut tanggal_selesai terbaru, 12 per halaman; filter tahun, tingkat, prodi, jenis.
   Data kartu: judul kegiatan, capaian, tingkat, penyelenggara, tahun, nama anggota yang setuju + prodi masing-masing,
   "dan N anggota lain" untuk yang tidak setuju dan anggota eksternal, foto pertama dari fotoPublik() via <x-foto-drive>
   (URL lh3.googleusercontent.com/d/<id>, alt = label, placeholder bila tidak ada/gagal).
   TANPA npm, email, tautan sertifikat/surat tugas, atau URL Drive selain URL gambar lh3 foto yang disetujui. Data dibentuk sebagai array (DTO) lalu di-cache "galeri:v{versi}:daftar:{md5(filter)}:{halaman}" 10 menit.
2. App\Livewire\Publik\DetailPrestasiPublik (/galeri/{prestasi}): 404 bila tidak tampilDiGaleri; data sama ditambah judul karya,
   deskripsi, seluruh foto dari fotoPublik(); cache item 10 menit.
3. Beranda menampilkan 6 prestasi terbaru dari cache galeri; halaman /kebijakan-privasi-galeri berisi teks persetujuan dan cara menarik.
4. Moderasi: Page App\Filament\Admin\Pages\ModerasiGaleri (permission galeri.moderasi): daftar prestasi yang tampil; Action
   SembunyikanDariGaleri / TampilkanKembali dengan alasan (activitylog, increment galeri:versi).
5. Pengaturan galeri_aktif false → /galeri menampilkan pesan "Galeri sedang tidak tersedia" (200) dan tidak ada data.
6. Test Pest: prestasi terverifikasi + pengaju setuju tampil; anggota tidak setuju tidak muncul namanya tetapi dihitung "dan 1 anggota
   lain"; npm dan URL drive.google.com tidak pernah ada di HTML (hanya lh3 untuk foto
   yang memenuhi syarat); foto dengan status_cek tidak_dapat_diakses tidak tampil; menarik persetujuan → langsung hilang pada request berikut (tanpa menunggu TTL);
   disembunyikan admin → 404 di detail; id prestasi draf → 404; query galeri tidak N+1 (preventLazyLoading).

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- `/galeri` tampil tanpa login; pemuatan kedua < 200 ms (dari cache).
- `view-source` halaman galeri tidak memuat NPM maupun email.
- Admin dapat menyembunyikan prestasi dan prestasi langsung hilang dari galeri.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(galeri): tambah galeri prestasi publik dan moderasi"
```

### F6.5 — Tutup fase F6
**Tujuan:** PR, merge, tag `v0.6.0`.

**Prompt (salin ke agen AI):**
```text
Tutup fase F6: pint --test, phpstan, test. Jalankan pemeriksaan aksesibilitas dasar halaman publik (label filter, alt gambar, kontras
warna Tailwind memenuhi AA, navigasi papan ketik pada filter dan paginasi) dan perbaiki temuan kecil. Perbarui CHANGELOG.md [0.6.0].
Cetak deskripsi PR.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- CI hijau; reviewer memeriksa `/galeri` dan `/kompetisi` di ponsel.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "docs(changelog): catat fase 6 katalog dan galeri publik"
git push -u origin feat/f6-katalog-galeri
gh pr create --base main --title "Fase 6: katalog kompetisi & galeri publik" --body-file pr-f6.md
gh pr merge --squash --subject "feat(galeri): fase 6 katalog kompetisi dan galeri publik" --delete-branch
git switch main && git pull
git tag -a v0.6.0 -m "Fase 6: katalog kompetisi & galeri publik"
git push origin main --tags
```

---

## F7 — Notifikasi & penjadwal

### F7.1 — Notifikasi surel dan database
**Branch:** `feat/f7-notifikasi-jadwal`
**Tujuan:** Semua pihak mendapat kabar perubahan status, permintaan konfirmasi, dan duplikat lewat antrean.

**Prompt (salin ke agen AI):**
```text
Ikuti 02-ARSITEKTUR.md bagian 10. Semua notifikasi implements ShouldQueue dengan ->onQueue('notifikasi'), afterCommit.
1. Buat notifikasi: PrestasiDiajukan, PrestasiPerluPerbaikan (memuat catatan verifikator), PrestasiTerverifikasi, PrestasiDitolak,
   PermintaanKonfirmasiPembimbing, PembimbingMenyangkal, KandidatDuplikatDitemukan, AnggotaTimDitambahkan (memberi tahu anggota bahwa
   ia dicantumkan dan dapat melapor bila keliru, serta mengatur persetujuan publikasi), KompetisiBaruTerbit, TautanBerkasTidakDapatDiakses (ke
   pengaju; juga admin-fakultas bila prestasi sudah terverifikasi; BR-30), BerkasEksporSiap,
   ImporSelesai (pindahkan dari F3.4 bila sudah ada).
   Kanal via(): database + mail sesuai tabel; mail memakai MailMessage berbahasa Indonesia dengan tombol ke URL panel yang tepat
   (Filament getUrl). Format database memakai Filament\Notifications\Notification::make()->...->getDatabaseMessage().
2. Listener App\Listeners\KirimNotifikasiStatusPrestasi pada StatusPrestasiBerubah: diajukan → admin-prodi prodi pengaju (bila tahap aktif)
   atau semua admin-fakultas; perlu_perbaikan/terverifikasi → semua anggota internal yang punya akun + pembimbing (terverifikasi);
   ditolak → pengaju. Listener lain untuk TautanBerkasTidakDapatDiakses (event dari F3.7), AnggotaTimDitambahkan, KandidatDuplikatDitemukan, PembimbingMenjawab (sangkal), konfirmasi
   pembimbing saat status konfirmasi menjadi menunggu.
3. KompetisiBaruTerbit: kirim (database saja) ke mahasiswa aktif prodi sasaran (atau semua) dengan chunk 200 di job terpisah antrean
   notifikasi.
4. Anggota tanpa akun user tidak dikirimi (catat jumlah dilewati di log).
5. Test Pest (Notification::fake): setiap transisi mengirim notifikasi ke penerima yang benar dan tidak ke pihak lain; notifikasi
   antre di 'notifikasi'; mail perlu perbaikan memuat catatan; anggota tanpa akun dilewati.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Mailpit (`http://localhost:8025`) menerima surel "Prestasi Anda perlu perbaikan" berisi catatan.
- Ikon lonceng Filament menampilkan notifikasi database.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(notifikasi): kirim notifikasi perubahan status prestasi"
```

### F7.2 — Kanal WhatsApp (Fonnte) dan preferensi notifikasi
**Tujuan:** Notifikasi WhatsApp opsional bagi pengguna yang mengaktifkannya.

**Prompt (salin ke agen AI):**
```text
1. App\Notifications\Channels\WhatsAppChannel: method send($notifiable, Notification $n) memanggil $n->toWhatsApp($notifiable) (string
   pesan ≤ 1000 karakter) dan mengirim POST ke config('services.fonnte.url') dengan header Authorization config('services.fonnte.token'),
   body target = nomor_wa, message. Timeout 10 detik; lempar exception bila respons gagal agar job di-retry (tries 3, backoff 60).
   Endpoint & format respons Fonnte perlu diverifikasi di dokumentasi resminya; bungkus di App\Services\FonnteClient agar mudah diganti.
2. Trait App\Notifications\Concerns\BisaWhatsApp: tambahkan 'whatsapp' ke via() hanya bila Pengaturan wa_aktif, notifiable->notif_wa_aktif
   dan nomor_wa valid (format 62xxxxxxxxx). Pasang pada PrestasiPerluPerbaikan, PrestasiTerverifikasi, PrestasiDitolak,
   PengingatTenggatKompetisi.
3. Portal & admin: Page ProfilNotifikasi — nomor_wa (normalisasi 08xx → 628xx), sakelar notif_wa_aktif, tombol "Kirim pesan uji"
   (rate limit 3/jam).
4. Jangan pernah mencatat token di log; log hanya status pengiriman dan 4 digit terakhir nomor.
5. Test Pest (Http::fake): pesan terkirim ke nomor yang benar; wa_aktif false → kanal tidak dipakai; respons 500 → exception (retry);
   normalisasi 0812... → 62812...; token tidak muncul di activity_log maupun laravel.log.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Dengan token uji, tombol "Kirim pesan uji" mengirim WA ke nomor pengguna.
- Gagal kirim WA tidak menggagalkan surel (cek Horizon: job WA gagal terpisah).

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(notifikasi): tambah kanal whatsapp fonnte dan preferensi pengguna"
```

### F7.3 — Penjadwal: pengingat, ringkasan, dan pembersihan
**Tujuan:** Tugas berkala sesuai 02-ARSITEKTUR bagian 9.

**Prompt (salin ke agen AI):**
```text
Buat perintah artisan dan jadwal di routes/console.php (semua ->onOneServer()->withoutOverlapping()):
1. puspresma:arsipkan-kompetisi (harian 00:30) memanggil ArsipkanKompetisiKedaluwarsa.
2. Job KirimPengingatTenggatKompetisi (harian 07:00): untuk kompetisi_minat dengan tenggat H-3 (diingatkan_h3_at null) dan H-1
   (diingatkan_h1_at null) kirim PengingatTenggatKompetisi, lalu isi kolom penanda (cegah ganda).
3. puspresma:ringkasan-antrean (Senin–Jumat 07:30): RingkasanAntreanVerifikasi ke admin-fakultas (jumlah diajukan, lolos_prodi,
   duplikat terbuka, pembimbing menunggu, tertua) dan ke admin-prodi (bila tahap aktif, hanya prodinya). Tidak mengirim bila semua nol.
4. puspresma:pengingat-pembimbing (Senin 08:00): PengingatKonfirmasiPembimbing ke dosen dengan permintaan menunggu > 7 hari.
5. puspresma:bersihkan-tmp (setiap jam): hapus berkas di storage/app/tmp berumur > 24 jam (impor, berkas galat, ekspor); bagian
   penandaan berkas_ekspor kedaluwarsa dilengkapi di F8.2. puspresma:periksa-tautan (Minggu 02:00): dispatch PeriksaTautanBerkas untuk
   semua tautan_berkas aktif (chunk 200, jeda 1 detik antar-permintaan agar tidak dibatasi Google); prioritaskan tautan prestasi
   terverifikasi dan foto galeri.
6. horizon:snapshot tiap 5 menit; activitylog:clean bulanan; queue:prune-failed --hours=720 bulanan.
7. Pastikan service puspresma-scheduler (php artisan schedule:work) berjalan dan dokumentasikan di README.
8. Test Pest (travel waktu dengan $this->travelTo): pengingat H-3 terkirim sekali walau perintah dijalankan dua kali; ringkasan tidak
   terkirim bila antrean kosong; berkas tmp berumur 25 jam terhapus sedangkan 23 jam tidak; periksa-tautan men-dispatch job per tautan; kompetisi lewat tenggat diarsipkan; daftar jadwal (Schedule events) memuat semua perintah di atas.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- `psp php artisan schedule:list` menampilkan semua jadwal di atas.
- Pengingat H-3 diterima mahasiswa berminat (uji dengan `schedule:test`).

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(jadwal): tambah penjadwal pengingat ringkasan dan pembersihan"
```

### F7.4 — Tutup fase F7
**Tujuan:** PR, merge, tag `v0.7.0`.

**Prompt (salin ke agen AI):**
```text
Tutup fase F7: pint --test, phpstan, test. Pastikan tidak ada notifikasi yang dikirim sinkron (cari kelas Notification tanpa ShouldQueue
dengan test arsitektur). Perbarui CHANGELOG.md [0.7.0]. Cetak deskripsi PR.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Test arsitektur "semua notifikasi ShouldQueue" hijau; CI hijau.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "test(notifikasi): pastikan seluruh notifikasi melalui antrean"
git push -u origin feat/f7-notifikasi-jadwal
gh pr create --base main --title "Fase 7: notifikasi & penjadwal" --body-file pr-f7.md
gh pr merge --squash --subject "feat(notifikasi): fase 7 notifikasi dan penjadwal" --delete-branch
git switch main && git pull
git tag -a v0.7.0 -m "Fase 7: notifikasi & penjadwal"
git push origin main --tags
```

---

## F8 — Laporan, dasbor & ekspor

### F8.1 — Dasbor statistik pimpinan
**Branch:** `feat/f8-laporan-ekspor`
**Tujuan:** Statistik per prodi/jenis/tingkat/capaian/tahun yang cepat dari cache Redis.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-02 (hitung per prodi anggota vs unik fakultas), BR-21.
1. App\Services\StatistikPrestasi dengan method (semua hanya status terverifikasi, parameter tahun dan ?prodiId):
   - ringkasan(): total prestasi unik, total mahasiswa unik berprestasi, jumlah internasional, nasional, persentase juara.
   - perProdi(): jumlah prestasi per prodi berdasarkan prestasi_anggota.prodi_id (COUNT DISTINCT prestasi_id per prodi).
   - perTingkat(), perJenis(), perBidang(), perCapaian(), perBulan() (12 bulan), trenTahunan(5 tahun).
   Satu kueri agregat per method (GROUP BY), tanpa memuat model. Hasil di-cache Cache::flexible
   ("statistik:v{versi}:{tahun}:{prodi|semua}:{method}", [600, 1800]).
2. Dasbor panel admin (Filament Dashboard) dengan widget: StatsOverview (ringkasan), Chart per tingkat (bar), per prodi (bar horizontal),
   tren bulanan (line), tabel 10 prestasi terverifikasi terbaru, kartu antrean (khusus admin). Filter dasbor: tahun dan prodi
   (admin-prodi dikunci ke prodinya). Pimpinan melihat semua prodi.
3. Catatan di dasbor: "Satu prestasi tim lintas prodi dihitung di setiap prodi anggota; total fakultas menghitung prestasi unik."
4. Test Pest: prestasi tim dengan anggota prodi A & B → perProdi A=1, B=1, ringkasan total=1; prestasi draf tidak terhitung;
   cache dipakai (DB::enableQueryLog jumlah kueri 0 pada panggilan kedua); versi naik → nilai baru; admin-prodi tidak dapat memilih
   prodi lain.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Dasbor tampil < 1 detik dengan 5.000 prestasi seed (DemoSeeder diperbesar).
- Angka per prodi dan total fakultas sesuai catatan BR-02.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(laporan): tambah dasbor statistik prestasi dengan cache redis"
```

### F8.2 — Ekspor rekap Excel/PDF dan rekap pribadi
**Tujuan:** LAP-01, LAP-02, LAP-06, LAP-07, LAP-09 — di-stream langsung; ekspor besar lewat antrean ke `storage/app/tmp` dan terhapus ≤ 24 jam.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-24 (ekspor hanya terverifikasi kecuali filter status dipilih admin), BR-25, BR-26, STANDAR-TEKNIS §1a butir 6.
1. Enum StatusBerkasEkspor; migrasi create_berkas_ekspor_table (03-SKEMA 3.23); model BerkasEkspor (HasUuids); berkas hanya di
   disk 'tmp' folder ekspor/{user_id}/. BerkasEksporPolicy (hanya pemilik; super-admin semua). UnduhBerkasEksporController
   GET /ekspor/{berkasEkspor}/unduh men-stream berkas dari tmp; 410 Gone bila kedaluwarsa.
2. Page App\Filament\Admin\Pages\Laporan (grup Laporan, permission laporan.ekspor): form filter (rentang tanggal / tahun, prodi
   [admin-prodi terkunci], jenis, bidang, tingkat, capaian, status [default terverifikasi]) dan tombol per laporan:
   - LAP-01 Rekap prestasi (XLSX & PDF): nomor registrasi, nama kegiatan, penyelenggara, jenis, bidang, kategori, tingkat, capaian,
     bentuk, tanggal mulai/selesai, moda/lokasi, jumlah peserta/PT/negara, pembimbing, anggota (NPM-Nama-Prodi; dipisah ;), status,
     dan (khusus admin-fakultas/admin-prodi) URL sertifikat dari snapshot verifikasi.
   - LAP-02 Rekap per anggota (XLSX): satu baris per prestasi × mahasiswa (+ prodi anggota, poin).
   - LAP-06 Peringkat poin (XLSX & PDF) dari PeringkatPoin F5.5.
   - LAP-09 Rekap katalog (XLSX): kompetisi, tanggal terbit, tenggat, jumlah minat, jumlah prestasi tertaut.
   Hitung jumlah baris lebih dulu. Bila ≤ 2.000 baris: stream langsung ke browser (response()->streamDownload dengan SimpleExcelWriter
   ke php://output, atau dompdf ->stream()) tanpa menulis berkas. Bila > 2.000: ambil Cache::lock("lock:ekspor:{user}:{jenis}", 120),
   rate limit 'ekspor' 10/jam, buat BerkasEkspor status antre, dispatch job antrean 'ekspor' (EksporRekapPrestasi/EksporPeringkatPoin/
   EksporRekapKatalog) yang menulis XLSX streaming (chunk 500, eager load) atau PDF (view resources/views/pdf/rekap-prestasi.blade.php,
   A4 landscape, kop FKIP Unsil, tanggal cetak, filter dipakai) ke disk tmp, set kedaluwarsa_at = now + 24 jam, kirim BerkasEksporSiap.
   Kolom kontak (email/HP) TIDAK disertakan untuk pimpinan dan admin-prodi.
3. BerkasEksporResource (read-only): daftar ekspor besar milik user, status, unduh, sisa waktu sebelum terhapus.
4. Lengkapi perintah puspresma:bersihkan-tmp (F7.3): hapus berkas tmp > 24 jam dan tandai berkas_ekspor kedaluwarsa.
5. Portal: tombol "Unduh rekap prestasi saya (PDF)" di Page PoinSaya/PrestasiSaya (LAP-07) — di-stream langsung (maks 200 baris),
   hanya prestasi terverifikasi milik mahasiswa; keterangan "bahan SKPI/portofolio, bukan dokumen resmi".
6. Activitylog setiap permintaan ekspor (jenis, filter, langsung/antrean).
7. Test Pest: ekspor kecil di-stream (respons 200, tidak ada berkas baru di storage); ekspor besar membuat job di antrean 'ekspor' dan
   berkas di tmp; admin-prodi meminta LAP-01 → hanya prestasi yang melibatkan prodinya; pengguna lain 403 saat unduh berkas ekspor
   milik orang lain (IDOR via id); berkas > 24 jam dihapus oleh perintah dan unduhan mengembalikan 410; klik ganda dalam 2 menit hanya
   membuat satu job; PDF rekap pribadi hanya memuat milik mahasiswa.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Ekspor kecil langsung terunduh tanpa jejak berkas di server.
- Ekspor 5.000 baris selesai di Horizon tanpa kehabisan memori, notifikasi "Berkas siap" muncul, dan berkas hilang setelah 24 jam.
- Berkas tidak dapat diunduh oleh pengguna lain.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(laporan): ekspor rekap prestasi excel pdf secara stream dan antrean"
```

### F8.3 — Laporan akreditasi (LAMDIK) dan IKU
**Tujuan:** LAP-03 dan LAP-04 dengan parameter yang dapat disesuaikan karena format resmi perlu diverifikasi.

**Prompt (salin ke agen AI):**
```text
1. LAP-03 "Statistik prodi untuk akreditasi": parameter prodi (wajib) dan tahun akhir TS. Tabel baris = tingkat (lokal, wilayah,
   nasional, internasional) × dua kelompok (akademik, non-akademik), kolom = TS-2, TS-1, TS, jumlah; plus lampiran daftar prestasi
   (nama kegiatan, tingkat, capaian, tahun, nama mahasiswa) sebagai sheet kedua. Judul kaki halaman: "Format menyesuaikan instrumen
   LAMDIK yang berlaku (perlu verifikasi)". XLSX & PDF.
2. LAP-04 "Rekap IKU mahasiswa berprestasi": parameter tahun, tingkat minimal (urutan tingkat, default nasional), hanya capaian is_juara
   (default ya), jenis kegiatan (default semua). Output: jumlah mahasiswa UNIK FKIP yang memenuhi, per prodi, dan daftar mahasiswa
   beserta prestasi pemenuhnya. Simpan parameter default di Pengaturan (iku_tingkat_minimal, iku_hanya_juara) dengan keterangan
   "definisi IKU perlu verifikasi". XLSX & PDF.
3. Keduanya memakai mekanisme BerkasEkspor + antrean dari F8.2 dan StatistikPrestasi (tambahkan method yang diperlukan, satu kueri
   agregat).
4. Test Pest: data contoh 3 tahun menghasilkan angka TS-2/TS-1/TS yang benar; mahasiswa dengan dua prestasi nasional dihitung sekali
   di LAP-04; perubahan tingkat minimal ke internasional mengurangi jumlah; admin-prodi hanya dapat memilih prodinya.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Admin prodi mengunduh LAP-03 untuk prodinya dengan tiga kolom tahun.
- LAP-04 menghitung mahasiswa unik.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(laporan): tambah laporan akreditasi dan rekap iku"
```

### F8.4 — Ekspor SIMKATMAWA dan penandaan dilaporkan
**Tujuan:** Ekspor kolom yang dapat dipetakan ke format isian SIMKATMAWA, termasuk URL bukti dari snapshot verifikasi, dan pelacakan yang sudah dilaporkan.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-27, BR-25, BR-28. Format kolom resmi SIMKATMAWA perlu verifikasi; karena itu pemetaan dibuat konfigurable.
1. config/simkatmawa.php: 'kolom' => array terurut [header_ekspor => sumber], dengan sumber berupa kunci dari App\Services\PemetaanSimkatmawa
   (mis. 'mahasiswa.npm', 'mahasiswa.nama', 'prodi.kode_pddikti', 'prodi.nama', 'kegiatan.nama', 'kegiatan.penyelenggara',
   'jenis.kode_simkatmawa', 'kategori.kode_simkatmawa', 'tingkat.kode_simkatmawa', 'capaian.kode_simkatmawa', 'bentuk',
   'kegiatan.tanggal_mulai' (Y-m-d), 'kegiatan.tanggal_selesai', 'kegiatan.url', 'kegiatan.jumlah_peserta', 'kegiatan.jumlah_pt',
   'kegiatan.jumlah_negara', 'pembimbing.nidn', 'pembimbing.nama', 'bukti.url_sertifikat', 'bukti.url_foto', 'bukti.url_surat_tugas',
   'prestasi.nomor_registrasi'). Kolom bukti.* diambil dari snapshot_tautan_verifikasi TERBARU (bukan tautan_berkas yang mungkin
   berubah); bila ada lebih dari satu tautan sejenis, gabungkan dengan " ; ". Header bawaan memakai label deskriptif Indonesia dan
   komentar "sesuaikan dengan templat isian SIMKATMAWA terbaru (perlu verifikasi)". Bila kode_simkatmawa kosong, pakai nama master dan
   tandai baris di kolom "Catatan Pemetaan". Tambahkan kolom "Status Tautan" (status_cek terakhir) agar admin tahu tautan yang perlu dicek.
2. Page App\Filament\Admin\Pages\EksporSimkatmawa (permission simkatmawa.ekspor): filter tahun, jenis, "hanya yang belum dilaporkan"
   (default ya); pratinjau 20 baris dengan tombol "Buka berkas" (route tautan.buka); tombol "Ekspor XLSX" dan "Ekspor CSV" (stream
   langsung bila ≤ 2.000 baris, selain itu antrean 'ekspor' + tmp ≤ 24 jam seperti F8.2). Satu baris per prestasi × anggota internal
   (perlu verifikasi apakah SIMKATMAWA meminta satu baris per mahasiswa; buat opsi 'satu_baris_per' => 'anggota'|'prestasi' di config).
   Catatan di halaman: bila SIMKATMAWA mensyaratkan berkas (bukan tautan), admin mengambil berkas dari Drive lewat tombol Buka berkas
   (perlu verifikasi); sistem tidak mengunduh atau membundel berkas.
3. Action TandaiDilaporkanSimkatmawa (bulk action di PrestasiResource dan tombol di Page setelah ekspor): hanya terverifikasi; isi
   dilaporkan_simkatmawa_at/oleh; aksi "Batalkan penandaan" wajib catatan; activitylog. Kolom & filter "Dilaporkan SIMKATMAWA" di
   PrestasiResource.
4. Test Pest: urutan kolom mengikuti config; mengubah config mengubah header tanpa ubah kode; URL bukti berasal dari snapshot walaupun
   tautan_berkas diubah setelah verifikasi; prestasi belum terverifikasi tidak ikut; filter belum dilaporkan bekerja; ekspor kecil tidak
   meninggalkan berkas di storage; admin-prodi 403 di halaman ini; penandaan prestasi draf ditolak.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Berkas XLSX SIMKATMAWA dapat dibuka, kolomnya cocok dengan `config/simkatmawa.php`, dan kolom URL bukti terisi dari snapshot.
- Setelah ditandai, prestasi tidak muncul lagi pada filter "belum dilaporkan".

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(simkatmawa): ekspor kolom terpetakan dengan url bukti dan penandaan"
```

### F8.5 — Surat Keterangan Prestasi ber-QR dan verifikasi publik
**Tujuan:** Surat resmi untuk beasiswa/SKPI/Pilmapres yang keasliannya dapat dicek lewat QR; PDF dan QR dibangkitkan saat diminta, tidak disimpan.

**Prompt (salin ke agen AI):**
```text
Aturan: BR-24, STANDAR-TEKNIS §1a butir 6.
1. Enum StatusSurat; migrasi create_surat_keterangan_prestasi_table dan create_surat_keterangan_prestasi_item_table (03-SKEMA 3.20–3.21;
   tanpa kolom path berkas); model SuratKeteranganPrestasi (HasUuids, LogsActivity).
2. Action TerbitkanSuratKeteranganPrestasi(Mahasiswa, array $prestasiIds, keperluan, periode, penandatangan, User): permission
   surat.terbitkan; semua prestasi harus terverifikasi dan melibatkan mahasiswa tsb; nomor dibuat dengan Cache::lock
   ("lock:nomor-surat-prestasi:{tahun}") + transaksi, format dari Pengaturan format_nomor_surat_prestasi (default
   "{urut}/PRS/FKIP/{tahun}", beri keterangan "format perlu ditetapkan tata usaha"). Hanya data yang disimpan, bukan PDF.
3. App\Services\CetakSuratKeteranganPrestasi::stream(Surat): render dompdf (kop FKIP Unsil, identitas mahasiswa tanpa NIK, tabel prestasi
   dari data saat terbit, tempat-tanggal, penandatangan, QR endroid/qr-code berisi route('verifikasi.surat', id) sebagai data URI PNG
   yang dibangkitkan saat itu) lalu ->stream() ke browser. Tidak ada berkas yang ditulis ke disk. Surat dicabut dicetak dengan watermark
   "DICABUT".
4. CabutSuratKeteranganPrestasi(Surat, User, alasan): status dicabut, alasan wajib.
5. SuratKeteranganPrestasiResource (admin-fakultas; admin-prodi & pimpinan lihat sesuai cakupan): form pilih mahasiswa → pilih prestasi
   terverifikasi miliknya (CheckboxList) → keperluan, periode, penandatangan. Aksi "Cetak PDF" (stream), "Cabut". Mahasiswa melihat dan
   mencetak surat miliknya di portal.
6. VerifikasiSuratController GET /verifikasi/{id} (throttle verifikasi-surat 30/menit/IP): tampilkan nomor, nama mahasiswa, prodi,
   tanggal terbit, status (Aktif/Dicabut dengan tanggal), daftar judul prestasi + tingkat + capaian. Tanpa NPM, email, tautan bukti.
   Cache "verifikasi-surat:{id}" 1 jam, dihapus saat dicabut. UUID tidak dikenal → halaman "tidak ditemukan" (404).
7. Test Pest: prestasi belum terverifikasi tidak dapat dimasukkan; nomor berurutan tanpa ganda (dua penerbitan paralel); cetak PDF
   mengembalikan application/pdf dan tidak menulis berkas ke storage; QR berisi URL verifikasi; halaman verifikasi tidak memuat NPM
   maupun URL Drive; surat dicabut menampilkan status Dicabut seketika; throttle 429 setelah 30 permintaan.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- PDF surat terunduh dengan QR; memindai QR membuka halaman verifikasi yang benar.
- Tidak ada berkas surat tersimpan di server.
- Surat yang dicabut langsung tampil "Dicabut".

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "feat(surat): terbitkan surat keterangan prestasi ber-qr secara stream"
```

### F8.6 — Tutup fase F8
**Tujuan:** PR, merge, tag `v0.8.0`.

**Prompt (salin ke agen AI):**
```text
Tutup fase F8: pint --test, phpstan, test. Uji beban ringan dengan DemoSeeder 10.000 prestasi: ukur waktu dasbor (cache dingin & hangat),
LAP-01 XLSX, ekspor SIMKATMAWA; catat hasil di docs/kinerja.md (tabel sederhana). Perbarui CHANGELOG.md [0.8.0]. Cetak deskripsi PR.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- CI hijau; `docs/kinerja.md` berisi angka pengukuran; reviewer mengunduh minimal 3 jenis laporan.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "docs(laporan): catat hasil uji kinerja laporan dan dasbor"
git push -u origin feat/f8-laporan-ekspor
gh pr create --base main --title "Fase 8: laporan, dasbor & ekspor" --body-file pr-f8.md
gh pr merge --squash --subject "feat(laporan): fase 8 laporan dasbor dan ekspor" --delete-branch
git switch main && git pull
git tag -a v0.8.0 -m "Fase 8: laporan, dasbor & ekspor"
git push origin main --tags
```

---

## F9 — Pengerasan & rilis

### F9.1 — Uji otorisasi menyeluruh dan header keamanan
**Branch:** `feat/f9-pengerasan`
**Tujuan:** Membuktikan tidak ada IDOR/kebocoran lintas peran dan memasang header keamanan.

**Prompt (salin ke agen AI):**
```text
Gunakan checklist keamanan di docs/vibecoding/05-UJI-PENERIMAAN.md bagian 3.
1. Buat tests/Feature/Keamanan/MatriksOtorisasiTest.php berbasis dataset Pest: untuk setiap peran (super-admin, admin-fakultas,
   admin-prodi A, pimpinan, dosen X, mahasiswa M) × setiap halaman/aksi penting (daftar & view prestasi prodi lain, buka tautan bukti via route tautan.buka,
   unduh berkas ekspor milik orang lain, verifikasi, buka verifikasi, impor, pengaturan, bobot poin, moderasi galeri, surat, horizon,
   activity log) → status yang diharapkan sesuai matriks PRD §3.2. Gunakan UUID record milik pihak lain untuk uji IDOR.
2. Test arsitektur: setiap Resource Filament memiliki policy terdaftar; tidak ada route yang memakai {id} numerik untuk model Prestasi,
   TautanBerkas, BerkasEkspor, SuratKeteranganPrestasi (harus id); tidak ada FileUpload/UploadedFile/Storage::put di app/ kecuali
   impor Excel dan penulisan ke disk tmp; tidak ada pemanggilan ->status = di luar app/Actions.
3. Middleware App\Http\Middleware\HeaderKeamanan: X-Content-Type-Options nosniff, X-Frame-Options SAMEORIGIN, Referrer-Policy
   strict-origin-when-cross-origin, Permissions-Policy (camera=(), microphone=(), geolocation=()), Content-Security-Policy dasar (img-src 'self' data:
   https://lh3.googleusercontent.com; frame-src https://drive.google.com https://docs.google.com) yang kompatibel
   dengan Filament/Livewire/Vite (uji di browser, longgarkan seperlunya dan dokumentasikan). HSTS hanya production.
4. Pastikan SESSION_SECURE_COOKIE=true, SESSION_HTTP_ONLY, SameSite=lax di .env.example produksi; APP_DEBUG=false.
5. Periksa XSS: deskripsi kompetisi & deskripsi prestasi dirender aman (escape atau purifier); test dengan payload <img onerror>.
   Periksa injeksi tautan: url berisi javascript:, data:, atau https://drive.google.com.evil.com ditolak TautanBerkasValid; label
   tautan di-escape.
6. Jalankan composer audit dan npm audit; perbaiki kerentanan tingkat high/critical bila ada pembaruan minor yang aman.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- `MatriksOtorisasiTest` hijau dengan ≥ 60 kasus.
- `psp composer audit` tanpa temuan high/critical.
- Header keamanan terlihat di DevTools tanpa memecah panel Filament.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "test(auth): uji matriks otorisasi dan tambah header keamanan"
```

### F9.2 — Optimasi kinerja
**Tujuan:** Menghilangkan N+1, memastikan indeks terpakai, dan menyiapkan cache produksi.

**Prompt (salin ke agen AI):**
```text
1. Aktifkan sementara pemantauan kueri (DB::listen di test) untuk halaman: antrean verifikasi (25 baris), Prestasi Saya, galeri,
   katalog, dasbor. Target: ≤ 15 kueri per halaman, tanpa LazyLoadingViolation. Perbaiki dengan eager loading/withCount.
2. Jalankan EXPLAIN untuk kueri utama (antrean per prodi & status, galeri, statistik perProdi, deteksi duplikat) dan tambahkan indeks
   yang kurang lewat MIGRASI BARU (jangan ubah migrasi lama). Dokumentasikan di docs/kinerja.md.
3. Pastikan perintah build produksi: php artisan optimize, filament:optimize, icons:cache, view:cache, event:cache tercantum di README
   bagian deploy.
4. Tambahkan test regresi jumlah kueri (expect(count($queries))->toBeLessThanOrEqual(15)) untuk halaman antrean verifikasi dan galeri.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Test jumlah kueri hijau; `docs/kinerja.md` memuat hasil EXPLAIN sebelum/sesudah.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "perf(verifikasi): optimalkan kueri antrean galeri dan statistik"
```

### F9.3 — Docker produksi (pola docker-apps)
**Tujuan:** Image produksi PHP-FPM 8.4 + Nginx, container queue (Horizon) dan scheduler, MySQL 8.4, Redis 7.

**Prompt (salin ke agen AI):**
```text
Ikuti STANDAR-TEKNIS.md bagian 7 (pola docker-apps seperti fkipapp/plp).
1. docker/Dockerfile multi-stage: stage node:22 untuk npm ci && npm run build; stage composer (composer install --no-dev --optimize-autoloader);
   stage akhir php:8.4-fpm-alpine dengan ekstensi pdo_mysql, redis, gd (jpeg, untuk QR/dompdf), zip, intl, bcmath, opcache, pcntl; user non-root;
   ARG APP_VERSION diteruskan ke env APP_VERSION.
2. docker/nginx/default.conf: root public, client_max_body_size 11M (hanya impor Excel ≤ 10 MB; tidak ada unggah bukti), blok akses ke /storage,
   gzip, header keamanan statis, fastcgi ke app:9000.
3. docker-compose.prod.yml: services app (php-fpm), web (nginx), queue (php artisan horizon), scheduler (php artisan schedule:work),
   mysql (8.4, volume, utf8mb4_0900_ai_ci), redis (7-alpine, appendonly yes, volume). Volume kecil storage/app/tmp bersama untuk app/queue/scheduler (berkas sementara ≤ 24 jam;
   tidak ada berkas pengguna yang perlu dipertahankan).
   Healthcheck memakai /api/health. Tidak ada port MySQL/Redis yang dibuka ke luar.
4. docker/entrypoint.sh: tunggu DB, php artisan migrate --force (hanya pada container app dengan env RUN_MIGRATIONS=true), optimize,
   filament:optimize, icons:cache (storage:link tidak diperlukan karena tidak ada berkas publik di server).
5. .env.production.example tanpa rahasia. Dokumentasikan di README bagian "Deploy produksi" (build dengan --build-arg APP_VERSION=$(git describe --tags)).
6. Uji lokal: docker compose -f docker-compose.prod.yml up -d --build, akses /api/health = ok, Horizon memproses job uji.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
Subpath produksi PERSIS STANDAR-TEKNIS §2.4: APP_URL & ASSET_URL = https://supportfkip.unsil.ac.id/puspresma,
SESSION_PATH=/puspresma, SESSION_COOKIE=puspresma_session, SESSION_SECURE_COOKIE=true, TRUSTED_PROXIES = IP reverse proxy,
npm run build dengan ASSET_URL terisi, dan blok location /puspresma/ untuk reverse proxy dicatat di docs/DEPLOY.md.
```

**Selesai bila:**
- Stack produksi lokal berjalan; `/api/health` ok; impor Excel 5 MB berhasil melalui Nginx; tidak ada volume penyimpanan berkas pengguna.
- Footer menampilkan versi dari tag git.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "build(deploy): tambah dockerfile dan compose produksi"
```

### F9.4 — Backup dan uji pulih
**Tujuan:** Backup harian database (retensi ≥ 30 hari) dan prosedur pemulihan teruji. Berkas bukti tidak ada di server (berada di Google Drive mahasiswa).

**Prompt (salin ke agen AI):**
```text
1. composer require spatie/laravel-backup; config/backup.php: sumber HANYA database mysql (tidak ada direktori berkas;
   storage/app/tmp dikecualikan), tujuan disk 'backup' (lokal volume terpisah; opsi S3/MinIO via env),
   enkripsi arsip dengan BACKUP_ARCHIVE_PASSWORD, strategi pembersihan: simpan semua 7 hari, harian 30 hari, mingguan 8 minggu,
   bulanan 6 bulan. Notifikasi gagal backup ke surel super-admin.
2. Jadwal: backup:run --only-db harian 01:00, backup:clean 01:30, backup:monitor harian 09:00.
3. docs/PEMULIHAN.md: langkah pulih ke server kosong (ambil arsip, dekripsi, import mysql, php artisan migrate --force,
   cek /api/health, cek sampel tautan bukti & snapshot terbaca), anjuran kepada fakultas agar bukti prestasi tingkat nasional/
   internasional juga disalin ke Shared Drive fakultas (tanggung jawab unit, bukan sistem), serta jadwal uji pulih tiap semester dan formulir catatan hasil uji.
4. Test Pest: konfigurasi backup hanya memuat database dan tidak memuat direktori storage; jadwal terdaftar.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- `psp php artisan backup:run` menghasilkan arsip terenkripsi.
- Uji pulih ke database kosong berhasil dan tautan bukti contoh (serta snapshot) tampil benar setelah pulih.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "chore(deploy): konfigurasi backup harian database dan prosedur pemulihan"
```

### F9.5 — Panduan pengguna dan perbaikan UAT
**Tujuan:** Panduan singkat per peran dan perbaikan temuan uji penerimaan.

**Prompt (salin ke agen AI):**
```text
1. Buat docs/panduan/ berisi panduan singkat (bahasa Indonesia, langkah bernomor, ≤ 2 halaman masing-masing): mahasiswa.md (login, mengisi
   prestasi tim, menyalin tautan Drive & mengatur berbagi, ajukan, perbaikan, persetujuan publikasi, katalog & minat, poin), dosen.md (konfirmasi pembimbing),
   admin-prodi.md (antrean tahap 1, rekap prodi, laporan akreditasi), admin-fakultas.md (verifikasi dengan membuka tautan, duplikat, ganti tautan, impor, katalog, galeri,
   SIMKATMAWA, surat, pengaturan, bobot poin), pimpinan.md (dasbor & laporan), super-admin.md (pengguna, MFA, Horizon, backup).
   Tautkan dari menu "Bantuan" di kedua panel (render hook ke halaman Blade yang merender markdown).
2. Terapkan perbaikan dari daftar temuan UAT yang diberikan manusia di bawah ini (tempelkan daftar temuan di sini sebelum menjalankan
   prompt). Untuk setiap temuan: perbaiki, tambah test regresi, catat di CHANGELOG bagian [Belum dirilis] → Diperbaiki.
   Temuan UAT:
   - <isi oleh penguji>

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Menu Bantuan tampil di kedua panel.
- Semua skenario UAT di `05-UJI-PENERIMAAN.md` bertanda ✓ atau memiliki tiket tindak lanjut yang disepakati.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "docs(readme): tambah panduan pengguna per peran dan perbaikan uat"
```
> Bila perbaikan UAT banyak, pecah menjadi beberapa commit `fix(<cakupan>): ...` sebelum commit dokumentasi ini.

### F9.6 — Panduan per peran: HTML + tangkapan layar di landing page
**Tujuan:** Setelah semua fitur selesai, membuat panduan pengguna **HTML** untuk setiap peran lengkap dengan tangkapan layar otomatis, tersaji di `/puspresma/panduan/` dan ditautkan dari landing page (STANDAR-TEKNIS §2.5).

Persiapan manual (lokal saja):
```bash
psp php artisan migrate:fresh --seeder=PanduanSeeder     # akun demo per peran + data rekaan
docker compose --profile panduan run --rm puspresma-panduan     # setelah skrip dibuat agen
```

**Prompt (salin ke agen AI):**
```text
Baca 01-PRD §3 (peran & hak akses), 05-UJI §2 (skenario per peran), STANDAR-TEKNIS §2.4–§2.5.
1. database/seeders/PanduanSeeder.php — hanya APP_ENV=local (lempar exception di lingkungan lain): satu akun demo
   per peran (panduan.<peran>@contoh.test, kata sandi dari env PANDUAN_PASSWORD, MFA dinonaktifkan khusus akun demo
   lokal) dan data rekaan secukupnya agar setiap layar terisi. Dilarang memakai data pribadi asli.
2. docker-compose.yml: service puspresma-panduan dengan profiles: ["panduan"], image mcr.microsoft.com/playwright
   (tag stabil terbaru), jaringan fkip-net, volume .:/work, working_dir /work, env PANDUAN_BASE_URL=http://gateway/puspresma
   dan PANDUAN_PASSWORD, command node tests/panduan/tangkap.mjs. Skrip tests/panduan/tangkap.mjs (Playwright,
   Chromium, viewport 1366×768; tambah 390×844 untuk alur yang dipakai di ponsel): untuk setiap peran → login →
   jalankan alur utama peran sesuai 05-UJI §2 → simpan public/panduan/img/<peran>/NN-<langkah>.png (lebar maks
   1366, dikompres). Kaburkan/hilangkan token, kode MFA, dan surel nyata bila tampil.
3. public/panduan/index.html (daftar peran + ringkasan aplikasi) dan public/panduan/<peran>.html untuk SETIAP
   peran: HTML statis mandiri (CSS di dalam berkas, tanpa CDN, tanpa JS wajib), bahasa Indonesia, berisi tujuan
   peran, cara masuk, langkah bernomor dengan gambar (alt deskriptif) + keterangan, tanya-jawab singkat, kontak
   admin, tanggal & versi aplikasi. Semua href/src RELATIF (img/<peran>/…, index.html) agar berfungsi di
   /puspresma/panduan/. Responsif di ponsel; siap cetak (CSS @media print).
4. Landing page aplikasi (halaman publik "/" — bila tidak ada, halaman login): tautan jelas "Panduan Pengguna" ke
   asset('panduan/index.html'); panel Filament: tautan panduan di menu pengguna/footer.
5. composer.json script "panduan" yang menjalankan service puspresma-panduan; README bagian "Memperbarui panduan".
6. Ganti panduan Markdown lama (docs/PANDUAN-*.md, bila ada) dengan tautan ke HTML ini agar tidak ada dua versi.
7. tests/Feature/PanduanTest.php: setiap peran di seeder peran punya public/panduan/<peran>.html dan tercantum di
   index.html; setiap <img src> menunjuk berkas yang ada; tidak ada href/src berawalan "/" atau "http://localhost";
   landing page memuat tautan panduan; ukuran total public/panduan ≤ 15 MB; PanduanSeeder menolak APP_ENV=production.
Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan
perubahan.
```

**Selesai bila:**
- `http://localhost:8080/puspresma/panduan/` menampilkan daftar peran; setiap halaman peran berisi tangkapan layar terbaru dan tautannya berfungsi.
- Landing page aplikasi menautkan panduan.
- `PanduanTest` dan seluruh test hijau.
- Langkah manusia: kartu aplikasi di landing page `supportfkip` (root `index.html`) diberi tautan `puspresma/` dan `puspresma/panduan/`.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "docs(panduan): tambah panduan html per peran dengan tangkapan layar"
```

### F9.7 — Rilis v1.0.0
**Tujuan:** Checklist go-live, PR akhir, tag `v1.0.0`, dan deploy.

**Prompt (salin ke agen AI):**
```text
Persiapan rilis v1.0.0:
1. Jalankan pint --test, phpstan, test --parallel; semua harus hijau.
2. Periksa checklist go-live di docs/vibecoding/05-UJI-PENERIMAAN.md bagian 4 dan laporkan butir yang belum terpenuhi (jangan
   mencentang butir yang memerlukan tindakan manusia; daftarkan saja).
3. Pastikan seeder produksi hanya menjalankan PeranDanIzinSeeder, SuperAdminSeeder, ProdiSeeder (data resmi), JenisKegiatanSeeder,
   KategoriLombaSeeder, TingkatSeeder, CapaianSeeder, PengaturanSeeder — TIDAK DemoSeeder dan TIDAK BobotPoinContohSeeder.
4. Perbarui CHANGELOG.md bagian [1.0.0] berisi ringkasan seluruh fitur dan daftar hal "perlu verifikasi" regulasi yang masih terbuka
   (format SIMKATMAWA termasuk apakah menerima tautan atau mensyaratkan berkas, ambang negara/PT, definisi IKU, format akreditasi, pola
   email mahasiswa, kemampuan berbagi Drive dari domain student ke unsil.ac.id, format nomor surat, kalender akademik).
5. Cetak deskripsi PR rilis dan langkah deploy.

Jangan ubah berkas di luar cakupan langkah ini. Setelah selesai jalankan pint dan test, lalu tampilkan ringkasan perubahan.
```

**Selesai bila:**
- Checklist go-live lengkap ditandatangani pemilik produk.
- Produksi menampilkan versi `1.0.0` di footer dan `/api/health` ok.

**Commit:**
```bash
psp ./vendor/bin/pint && psp php artisan test \
  && git add -A \
  && git commit -m "docs(changelog): siapkan rilis v1.0.0"
git push -u origin feat/f9-pengerasan
gh pr create --base main --title "Fase 9: pengerasan & rilis v1.0.0" --body-file pr-f9.md
gh pr merge --squash --subject "feat(deploy): fase 9 pengerasan dan rilis" --delete-branch
git switch main && git pull
git tag -a v1.0.0 -m "Rilis produksi pertama Puspresma FKIP Unsil"
git push origin main --tags
# deploy: di server
# docker compose -f docker-compose.prod.yml build --build-arg APP_VERSION=v1.0.0 && docker compose -f docker-compose.prod.yml up -d
```

Perbaikan setelah rilis memakai branch `fix/<ringkas>` dan tag patch (`v1.0.1`, …) sesuai `STANDAR-GIT.md`.

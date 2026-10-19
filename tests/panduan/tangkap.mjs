// Tangkapan layar panduan. Jalankan lewat `composer panduan` (Playwright). Butuh PanduanSeeder + PANDUAN_PASSWORD.
import { readFileSync, mkdirSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PLAYWRIGHT_PATH ?? 'playwright');

const akar = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const dasar = (process.env.PANDUAN_BASE_URL ?? 'http://localhost:8023').replace(/\/$/, '');
const sandi = process.env.PANDUAN_PASSWORD;
if (!sandi) {
    console.error('PANDUAN_PASSWORD wajib diisi');
    process.exit(1);
}

const alur = JSON.parse(readFileSync(join(akar, 'tests/panduan/alur.json'), 'utf8'));
const hanya = process.argv[2];
const peranTarget = Object.keys(alur).filter((p) => !hanya || p === hanya);
const peramban = await chromium.launch({ args: ['--disable-dev-shm-usage', '--disable-gpu', '--no-sandbox'] });

const sembunyikan = async (halaman) => {
    // kaburkan surel pengguna di menu bila tampil (surel demo @contoh.test, tetap dikaburkan sebagai kebiasaan)
    await halaman.addStyleTag({ content: '.fi-user-menu, [data-sensitif] { filter: blur(4px); }' });
};

for (const peran of peranTarget) {
    const { panel, langkah } = alur[peran];
    const keluaran = join(akar, 'public/panduan/img', peran);
    mkdirSync(keluaran, { recursive: true });

    const konteks = await peramban.newContext({ viewport: { width: 1366, height: 768 }, locale: 'id-ID' });
    const halaman = await konteks.newPage();
    let masuk = false;

    for (const l of langkah) {
        console.log(`  → ${peran}/${l.nama}`);
        if (l.anon) {
            await halaman.goto(`${dasar}/${l.url}`, { waitUntil: 'networkidle' });
            await halaman.screenshot({ path: join(keluaran, `${l.nama}.png`) });
            continue;
        }
        if (!masuk) {
            await halaman.goto(`${dasar}/${panel}/login`, { waitUntil: 'networkidle' });
            await halaman.fill('input[type=email]', `panduan.${peran}@contoh.test`);
            await halaman.fill('input[type=password]', sandi);
            await halaman.click('button[type=submit]');
            try {
                await halaman.waitForURL((u) => !u.pathname.endsWith('/login'), { timeout: 45000 });
            } catch (galat) {
                await halaman.screenshot({ path: join('/tmp', `gagal-login-${peran}.png`) });
                throw galat;
            }
            masuk = true;
        }
        await halaman.goto(`${dasar}/${l.url}`, { waitUntil: 'networkidle' });
        await sembunyikan(halaman);
        await halaman.screenshot({ path: join(keluaran, `${l.nama}.png`) });

        if (l.ponsel) {
            await halaman.setViewportSize({ width: 390, height: 844 });
            await halaman.goto(`${dasar}/${l.url}`, { waitUntil: 'networkidle' });
            await halaman.screenshot({ path: join(keluaran, `${l.nama}-ponsel.png`) });
            await halaman.setViewportSize({ width: 1366, height: 768 });
        }
    }
    await konteks.close();
    console.log(`✓ ${peran}: ${langkah.length} layar`);
}

await peramban.close();

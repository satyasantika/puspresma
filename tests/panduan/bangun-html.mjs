// Membangun public/panduan/*.html dari alur.json + peran.json. HTML statis mandiri: CSS inline, tanpa CDN, href/src relatif
// (aman di subpath mana pun). Tema mengikuti landing page. Panduan super-admin tidak dipublikasikan di sini (hanya di /bantuan).
import { existsSync, readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const akar = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const alur = JSON.parse(readFileSync(join(akar, 'tests/panduan/alur.json'), 'utf8'));
const peran = JSON.parse(readFileSync(join(akar, 'tests/panduan/peran.json'), 'utf8'));
const keluaran = join(akar, 'public/panduan');
mkdirSync(keluaran, { recursive: true });

const versi = process.env.APP_VERSION ?? readFileSync(join(akar, '.env.production.example'), 'utf8').match(/^APP_VERSION=(.+)$/m)?.[1] ?? '1.0.0';
const tanggal = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
const esc = (t) => String(t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
const kalimatPertama = (t) => `${t.split('. ')[0].replace(/\.$/, '')}.`;
const panelPeran = (p) => (alur[p].panel === 'portal' ? 'portal' : 'admin');

const logo = '<svg viewBox="0 0 48 48" width="36" height="36" aria-hidden="true"><rect width="48" height="48" rx="12" fill="#fbbf24"/><path d="M15 12h18v7c0 6-4 10-9 10s-9-4-9-10v-7Zm-5 2h5v3c0 2 1 3 3 3v2c-5 0-8-3-8-7v-1Zm28 0h5v1c0 4-3 7-8 7v-2c2 0 3-1 3-3v-3ZM21 30h6v4h5v3H16v-3h5v-4Z" fill="#1e1b4b"/></svg>';

const css = `
:root{--ink:#1e293b;--muted:#475569;--garis:#e2e8f0;--aksen:#3730a3;--aksen2:#312e81;--dalam:#1e1b4b;--emas:#fbbf24;--latar:#f8fafc}
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;font:16px/1.65 'Instrument Sans',ui-sans-serif,system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;color:var(--ink);background:var(--latar)}
a{color:var(--aksen)}a:focus-visible,button:focus-visible{outline:3px solid var(--emas);outline-offset:2px}
.atas{background:linear-gradient(150deg,#1e1b4b 0%,#312e81 55%,#4338ca 100%);color:#fff}
.atas .dalam{max-width:60rem;margin:0 auto;padding:1rem 1rem 1.5rem}
.bar{display:flex;flex-wrap:wrap;align-items:center;gap:.75rem 1.25rem}.merek{display:inline-flex;align-items:center;gap:.65rem;color:#fff;text-decoration:none;font-weight:700}
.merek small{display:block;font-weight:500;font-size:.72rem;color:#c7d2fe}.bar .kanan{margin-inline-start:auto;display:flex;gap:1rem;font-size:.9rem}.bar .kanan a{color:#e0e7ff;text-decoration:none}.bar .kanan a:hover{color:#fff;text-decoration:underline}
.atas h1{margin:1.25rem 0 .25rem;font-size:clamp(1.6rem,4vw,2.25rem);line-height:1.2;letter-spacing:-.02em}.atas p{margin:0;color:#c7d2fe;max-width:40rem}
nav.peran{display:flex;flex-wrap:wrap;gap:.5rem;margin:1.25rem 0 0}nav.peran a{padding:.3rem .85rem;border:1px solid rgba(255,255,255,.28);border-radius:999px;color:#e0e7ff;text-decoration:none;font-size:.9rem}
nav.peran a:hover{background:rgba(255,255,255,.12)}nav.peran a[aria-current]{background:var(--emas);color:var(--dalam);border-color:var(--emas);font-weight:600}
main,footer{max-width:60rem;margin:0 auto;padding:1rem}main{padding-bottom:2rem}
h2{font-size:1.3rem;margin:2.25rem 0 .5rem;color:var(--dalam);letter-spacing:-.01em}
.tombol{display:inline-block;margin-top:.5rem;padding:.6rem 1.1rem;border-radius:.55rem;background:var(--aksen);color:#fff;text-decoration:none;font-weight:600}.tombol:hover{background:var(--aksen2)}
ol.langkah{list-style:none;counter-reset:l;padding:0;margin:1rem 0}ol.langkah>li{counter-increment:l;position:relative;margin:1rem 0;padding:1.1rem 1.1rem 1.1rem 3.6rem;background:#fff;border:1px solid var(--garis);border-radius:1rem;box-shadow:0 1px 2px rgba(15,23,42,.05)}
ol.langkah>li::before{content:counter(l);position:absolute;left:1rem;top:1.05rem;display:grid;place-items:center;width:1.85rem;height:1.85rem;border-radius:50%;background:var(--aksen);color:#fff;font-weight:700;font-size:.85rem}
figure{margin:.85rem 0 0}figure img{max-width:100%;height:auto;border:1px solid var(--garis);border-radius:.6rem;background:#fff}figure.ponsel img{max-width:15rem}figcaption{font-size:.85rem;color:var(--muted);margin-top:.25rem}
dl{margin:0}dt{font-weight:600;margin-top:1rem;color:var(--dalam)}dd{margin:.15rem 0 0;color:var(--muted)}
.kartu{display:grid;gap:1rem;grid-template-columns:repeat(auto-fill,minmax(16rem,1fr));margin-top:1rem}
.kartu a{display:block;height:100%;padding:1.1rem;background:#fff;border:1px solid var(--garis);border-radius:1rem;text-decoration:none;color:var(--ink);box-shadow:0 1px 2px rgba(15,23,42,.05);transition:box-shadow .15s,border-color .15s}
.kartu a:hover{border-color:#a5b4fc;box-shadow:0 6px 18px -8px rgba(55,48,163,.35)}.kartu strong{display:block;color:var(--dalam);font-size:1.05rem}.kartu span{display:block;margin-top:.35rem;color:var(--muted);font-size:.92rem}
.kartu em{display:inline-block;margin-top:.7rem;font-style:normal;font-weight:600;font-size:.9rem;color:var(--aksen)}
footer{color:var(--muted);font-size:.85rem;border-top:1px solid var(--garis);margin-top:1rem}
@media print{.atas{background:#fff;color:#000}.atas p,.merek small,.bar .kanan a,nav.peran a{color:#000}.atas nav,.bar .kanan{display:none}body{background:#fff}ol.langkah>li{box-shadow:none;break-inside:avoid}figure img{max-width:70%}}`;

const rangka = ({ judul, h1, lead, badan, aktif }) => `<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#312e81"><title>${esc(judul)} · Panduan Puspresma FKIP Unsil</title><style>${css}</style></head>
<body><header class="atas"><div class="dalam">
<div class="bar"><a class="merek" href="../" aria-label="Puspresma FKIP Unsil, ke beranda">${logo}<span>Puspresma<small>FKIP Universitas Siliwangi</small></span></a>
<div class="kanan"><a href="../">← Beranda</a><a href="../galeri">Galeri prestasi</a><a href="../kompetisi">Kompetisi</a></div></div>
<h1>${esc(h1)}</h1><p>${esc(lead)}</p>
<nav class="peran" aria-label="Panduan per peran"><a href="index.html"${aktif === 'index' ? ' aria-current="page"' : ''}>Semua panduan</a>${Object.keys(peran).map((p) => `<a href="${p}.html"${aktif === p ? ' aria-current="page"' : ''}>${esc(peran[p].nama)}</a>`).join('')}</nav>
</div></header>
<main>${badan}</main>
<footer>Diperbarui ${tanggal} · Aplikasi versi ${esc(versi)} · Butuh bantuan? Hubungi admin fakultas FKIP Unsil.</footer></body></html>`;

const adaGambar = (p, nama) => existsSync(join(keluaran, 'img', p, nama));

for (const [p, meta] of Object.entries(peran)) {
    const langkah = alur[p].langkah.map((l) => {
        const gambar = adaGambar(p, `${l.nama}.png`)
            ? `<figure><img src="img/${p}/${l.nama}.png" alt="Tangkapan layar: ${esc(l.judul)}" loading="lazy"><figcaption>${esc(l.judul)}</figcaption></figure>` : '';
        const ponsel = adaGambar(p, `${l.nama}-ponsel.png`)
            ? `<figure class="ponsel"><img src="img/${p}/${l.nama}-ponsel.png" alt="Tampilan ponsel: ${esc(l.judul)}" loading="lazy"><figcaption>Tampilan di ponsel</figcaption></figure>` : '';
        return `<li><strong>${esc(l.judul)}.</strong> ${esc(l.ket)}${gambar}${ponsel}</li>`;
    }).join('\n');
    const tanya = meta.tanya.map(([q, a]) => `<dt>${esc(q)}</dt><dd>${esc(a)}</dd>`).join('');
    writeFileSync(join(keluaran, `${p}.html`), rangka({
        judul: `Panduan ${meta.nama}`,
        h1: `Panduan ${meta.nama}`,
        lead: kalimatPertama(meta.tujuan),
        aktif: p,
        badan: `
<h2>Tujuan</h2><p>${esc(meta.tujuan)}</p>
<h2>Cara masuk</h2><p>${esc(meta.masuk)}</p><a class="tombol" href="../${panelPeran(p)}">Buka ${panelPeran(p) === 'portal' ? 'Portal' : 'Panel Admin'}</a>
<h2>Langkah-langkah</h2><ol class="langkah">${langkah}</ol>
<h2>Tanya-jawab</h2><dl>${tanya}</dl>`,
    }));
}

const kartu = Object.entries(peran).map(([p, m]) => `<a href="${p}.html"><strong>${esc(m.nama)}</strong><span>${esc(kalimatPertama(m.tujuan))}</span><em>Buka panduan →</em></a>`).join('');
writeFileSync(join(keluaran, 'index.html'), rangka({
    judul: 'Panduan Pengguna',
    h1: 'Panduan Pengguna',
    lead: 'Langkah demi langkah bergambar untuk setiap peran di Puspresma, Pusat Prestasi Mahasiswa FKIP Universitas Siliwangi.',
    aktif: 'index',
    badan: `
<p>Puspresma mencatat prestasi akademik dan non-akademik mahasiswa sebagai bukti akreditasi, laporan kinerja, bahan SIMKATMAWA, dan seleksi beasiswa. Bukti prestasi berupa tautan Google Drive; tidak ada unggah berkas ke server.</p>
<h2>Pilih peran Anda</h2><div class="kartu">${kartu}</div>`,
}));

console.log(`✓ ${Object.keys(peran).length + 1} halaman HTML (versi ${versi})`);

// Membangun public/panduan/*.html dari alur.json + peran.json (F9.6). HTML statis mandiri: CSS inline, tanpa CDN, href/src relatif.
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

const css = `
:root{--ink:#1e293b;--muted:#475569;--garis:#e2e8f0;--aksen:#3730a3;--latar:#f8fafc}
*{box-sizing:border-box}body{margin:0;font:16px/1.6 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:var(--ink);background:var(--latar)}
header,main,footer{max-width:60rem;margin:0 auto;padding:1rem}header{border-bottom:1px solid var(--garis)}
a{color:var(--aksen)}h1{font-size:1.75rem;margin:.5rem 0}h2{font-size:1.25rem;margin-top:2rem}
nav.peran{display:flex;flex-wrap:wrap;gap:.5rem;margin:.75rem 0}nav.peran a{padding:.25rem .75rem;border:1px solid var(--garis);border-radius:.375rem;background:#fff;text-decoration:none}
nav.peran a[aria-current]{background:var(--aksen);color:#fff;border-color:var(--aksen)}
ol.langkah{padding-left:1.25rem}ol.langkah li{margin:1.5rem 0}figure{margin:.75rem 0}figure img{max-width:100%;height:auto;border:1px solid var(--garis);border-radius:.5rem;background:#fff}
figure.ponsel img{max-width:16rem}figcaption{font-size:.875rem;color:var(--muted)}dt{font-weight:600;margin-top:.75rem}dd{margin:0 0 .5rem}
.kartu{display:grid;gap:1rem;grid-template-columns:repeat(auto-fill,minmax(15rem,1fr))}.kartu a{display:block;padding:1rem;background:#fff;border:1px solid var(--garis);border-radius:.5rem;text-decoration:none}
footer{color:var(--muted);font-size:.875rem;border-top:1px solid var(--garis)}
@media print{header nav,.kartu a{border:0}body{background:#fff}figure img{max-width:70%}a{color:inherit}}`;

const rangka = (judul, badan, aktif) => `<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>${esc(judul)} · Panduan Puspresma FKIP Unsil</title><style>${css}</style></head>
<body><header><strong>Panduan Pengguna Puspresma FKIP Unsil</strong>
<nav class="peran" aria-label="Panduan per peran"><a href="index.html"${aktif === 'index' ? ' aria-current="page"' : ''}>Beranda panduan</a>${Object.keys(peran).map((p) => `<a href="${p}.html"${aktif === p ? ' aria-current="page"' : ''}>${esc(peran[p].nama)}</a>`).join('')}</nav></header>
<main>${badan}</main>
<footer>Diperbarui ${tanggal} · Aplikasi versi ${esc(versi)} · Butuh bantuan? Hubungi admin fakultas FKIP Unsil.</footer></body></html>`;

const adaGambar = (p, nama) => existsSync(join(keluaran, 'img', p, nama));

for (const [p, meta] of Object.entries(peran)) {
    const langkah = alur[p].langkah.map((l, i) => {
        const gambar = adaGambar(p, `${l.nama}.png`)
            ? `<figure><img src="img/${p}/${l.nama}.png" alt="Tangkapan layar: ${esc(l.judul)}" loading="lazy"><figcaption>${esc(l.judul)}</figcaption></figure>` : '';
        const ponsel = adaGambar(p, `${l.nama}-ponsel.png`)
            ? `<figure class="ponsel"><img src="img/${p}/${l.nama}-ponsel.png" alt="Tampilan ponsel: ${esc(l.judul)}" loading="lazy"><figcaption>Tampilan di ponsel</figcaption></figure>` : '';
        return `<li><strong>${esc(l.judul)}.</strong> ${esc(l.ket)}${gambar}${ponsel}</li>`;
    }).join('\n');
    const tanya = meta.tanya.map(([q, a]) => `<dt>${esc(q)}</dt><dd>${esc(a)}</dd>`).join('');
    writeFileSync(join(keluaran, `${p}.html`), rangka(`Panduan ${meta.nama}`, `
<h1>Panduan ${esc(meta.nama)}</h1>
<h2>Tujuan</h2><p>${esc(meta.tujuan)}</p>
<h2>Cara masuk</h2><p>${esc(meta.masuk)}</p>
<h2>Langkah-langkah</h2><ol class="langkah">${langkah}</ol>
<h2>Tanya-jawab</h2><dl>${tanya}</dl>`, p));
}

const kartu = Object.entries(peran).map(([p, m]) => `<a href="${p}.html"><strong>${esc(m.nama)}</strong><br><span>${esc(m.tujuan.split('. ')[0])}.</span></a>`).join('');
writeFileSync(join(keluaran, 'index.html'), rangka('Panduan Pengguna', `
<h1>Panduan Pengguna</h1>
<p>Puspresma (Pusat Prestasi Mahasiswa) FKIP Universitas Siliwangi mencatat prestasi akademik dan non-akademik mahasiswa sebagai bukti akreditasi, laporan kinerja, bahan SIMKATMAWA, dan seleksi beasiswa. Bukti berupa tautan Google Drive; tidak ada unggah berkas ke server.</p>
<h2>Pilih peran Anda</h2><div class="kartu">${kartu}</div>`, 'index'));

console.log(`✓ ${Object.keys(peran).length + 1} halaman HTML (versi ${versi})`);

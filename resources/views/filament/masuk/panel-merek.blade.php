@php($panelId = filament()->getCurrentPanel()?->getId())
<aside class="psp-merek" aria-label="Tentang Puspresma">
    <div class="psp-merek-isi">
        <a href="{{ route('beranda') }}" class="psp-merek-logo" aria-label="Puspresma FKIP Unsil, ke beranda">
            <svg viewBox="0 0 48 48" width="44" height="44" aria-hidden="true"><rect width="48" height="48" rx="12" fill="#fbbf24"/><path d="M15 12h18v7c0 6-4 10-9 10s-9-4-9-10v-7Zm-5 2h5v3c0 2 1 3 3 3v2c-5 0-8-3-8-7v-1Zm28 0h5v1c0 4-3 7-8 7v-2c2 0 3-1 3-3v-3ZM21 30h6v4h5v3H16v-3h5v-4Z" fill="#1e1b4b"/></svg>
            <span>Puspresma<small>FKIP Universitas Siliwangi</small></span>
        </a>

        <div class="psp-merek-badan">
            <p class="psp-merek-label">{{ $panelId === 'portal' ? 'Portal Mahasiswa &amp; Dosen' : 'Panel Pengelola' }}</p>
            <h2>Setiap prestasi tercatat, terverifikasi, dan terbuka.</h2>
            <p class="psp-merek-lead">Pusat Prestasi Mahasiswa untuk akreditasi, laporan kinerja, dan penghargaan bagi mahasiswa FKIP.</p>

            <ul class="psp-merek-daftar">
                <li><span aria-hidden="true">✓</span> Ajukan prestasi dengan tautan bukti Google Drive</li>
                <li><span aria-hidden="true">✓</span> Verifikasi bertahap oleh prodi dan fakultas</li>
                <li><span aria-hidden="true">✓</span> Galeri publik berbasis persetujuan, surat keterangan ber-QR</li>
            </ul>
        </div>

        <div class="psp-merek-kaki">
            <a href="{{ route('beranda') }}">← Beranda</a>
            <a href="{{ asset('panduan/index.html') }}">Panduan pengguna</a>
            <a href="{{ route('galeri.index') }}">Galeri prestasi</a>
        </div>
    </div>
</aside>

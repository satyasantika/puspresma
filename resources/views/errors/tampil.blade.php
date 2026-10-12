{{-- Halaman galat mandiri: tanpa Vite, sesi, atau basis data agar tetap tampil saat sistem bermasalah. --}}
@php
    $isi = \App\Support\HalamanGalat::untuk((int) $kode);
    $tunggu = isset($exception) && method_exists($exception, 'getHeaders') ? ($exception->getHeaders()['Retry-After'] ?? null) : null;
    $beranda = url('/');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#312e81">
    @if ((int) $kode === 503)
        <meta http-equiv="refresh" content="{{ is_numeric($tunggu) ? max(10, (int) $tunggu) : 30 }}">
    @endif
    <title>{{ $kode }} · {{ $isi['judul'] }} · Puspresma FKIP Unsil</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; color: #1e293b; line-height: 1.55;
               background: linear-gradient(150deg, #1e1b4b 0%, #312e81 55%, #4338ca 100%); position: relative; overflow-x: hidden; }
        body::before { content: ""; position: absolute; width: 32rem; height: 32rem; right: -10rem; top: -10rem; border-radius: 50%; background: radial-gradient(circle, rgba(251,191,36,.28), transparent 65%); }
        body::after { content: ""; position: absolute; width: 28rem; height: 28rem; left: -10rem; bottom: -10rem; border-radius: 50%; background: radial-gradient(circle, rgba(129,140,248,.35), transparent 65%); }
        header, main, footer { position: relative; z-index: 1; }
        header { padding: 1.25rem 1.5rem; }
        .merek { display: inline-flex; align-items: center; gap: .75rem; color: #fff; text-decoration: none; font-weight: 700; font-size: 1.125rem; }
        .merek small { display: block; font-weight: 500; font-size: .7rem; color: #c7d2fe; }
        main { flex: 1; display: grid; place-items: center; padding: 1rem 1.25rem 3rem; }
        .kartu { width: 100%; max-width: 38rem; background: #fff; border-radius: 1.5rem; padding: 2.25rem 2rem; box-shadow: 0 25px 60px -15px rgba(15,23,42,.45); }
        .kode { margin: 0; font-size: clamp(3.5rem, 12vw, 5.5rem); line-height: 1; font-weight: 800; letter-spacing: -.04em; color: #3730a3; }
        .kode span { color: #f59e0b; }
        h1 { margin: .75rem 0 0; font-size: clamp(1.35rem, 3.5vw, 1.75rem); line-height: 1.25; color: #0f172a; letter-spacing: -.01em; }
        .pesan { margin: .75rem 0 0; color: #475569; }
        ul { margin: 1.25rem 0 0; padding: 1rem 1rem 1rem 2.25rem; background: #f8fafc; border-radius: .75rem; color: #475569; font-size: .9rem; }
        li + li { margin-top: .35rem; }
        .aksi { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.75rem; }
        .tombol { display: inline-block; padding: .7rem 1.25rem; border-radius: .6rem; font: inherit; font-weight: 600; font-size: .95rem; text-decoration: none; cursor: pointer; border: 1px solid transparent; }
        .tombol:focus-visible { outline: 3px solid #fbbf24; outline-offset: 2px; }
        .utama { background: #3730a3; color: #fff; }
        .utama:hover { background: #312e81; }
        .kedua { background: #fff; color: #3730a3; border-color: #c7d2fe; }
        .kedua:hover { background: #eef2ff; }
        .tunggu { margin: 1rem 0 0; font-size: .85rem; color: #64748b; }
        footer { padding: 1rem 1.5rem 1.5rem; text-align: center; font-size: .78rem; color: #a5b4fc; }
        @media (max-width: 480px) { .kartu { padding: 1.75rem 1.25rem; } .tombol { flex: 1 1 100%; text-align: center; } }
    </style>
</head>
<body>
    <header>
        <a class="merek" href="{{ $beranda }}" aria-label="Puspresma FKIP Unsil, ke beranda">
            <svg viewBox="0 0 48 48" width="36" height="36" aria-hidden="true"><rect width="48" height="48" rx="12" fill="#fbbf24"/><path d="M15 12h18v7c0 6-4 10-9 10s-9-4-9-10v-7Zm-5 2h5v3c0 2 1 3 3 3v2c-5 0-8-3-8-7v-1Zm28 0h5v1c0 4-3 7-8 7v-2c2 0 3-1 3-3v-3ZM21 30h6v4h5v3H16v-3h5v-4Z" fill="#1e1b4b"/></svg>
            <span>Puspresma<small>FKIP Universitas Siliwangi</small></span>
        </a>
    </header>

    <main>
        <section class="kartu" role="alert" aria-labelledby="judul-galat">
            <p class="kode" aria-label="Kode galat {{ $kode }}">{{ substr((string) $kode, 0, -1) }}<span>{{ substr((string) $kode, -1) }}</span></p>
            <h1 id="judul-galat">{{ $isi['judul'] }}</h1>
            <p class="pesan">{{ $isi['pesan'] }}</p>

            @if ($isi['saran'] !== [])
                <ul>
                    @foreach ($isi['saran'] as $saran)
                        <li>{{ $saran }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="aksi">
                @if ($isi['aksi'] === 'masuk')
                    <a class="tombol utama" href="{{ url('/masuk') }}">Masuk</a>
                    <a class="tombol kedua" href="{{ $beranda }}">Ke beranda</a>
                @elseif ($isi['aksi'] === 'muat-ulang')
                    <a class="tombol utama" href="" onclick="location.reload();return false;">Muat ulang</a>
                    <a class="tombol kedua" href="{{ $beranda }}">Ke beranda</a>
                @else
                    <a class="tombol utama" href="{{ $beranda }}">Ke beranda</a>
                    <a class="tombol kedua" href="" onclick="history.length > 1 ? history.back() : (location.href = '{{ $beranda }}');return false;">Kembali</a>
                @endif
            </div>

            @if (is_numeric($tunggu))
                <p class="tunggu">Coba lagi dalam sekitar {{ (int) $tunggu }} detik.</p>
            @endif
        </section>
    </main>

    <footer>Puspresma FKIP Universitas Siliwangi</footer>
</body>
</html>

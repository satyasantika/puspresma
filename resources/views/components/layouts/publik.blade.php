@props(['judul' => null, 'deskripsi' => null, 'penuh' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul ? $judul.' · ' : '' }}{{ config('app.name') }}</title>
    <meta name="description" content="{{ $deskripsi ?: 'Pusat Prestasi Mahasiswa FKIP Universitas Siliwangi: pencatatan, verifikasi, dan pelaporan prestasi mahasiswa.' }}">
    <meta name="theme-color" content="#312e81">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-800 antialiased">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:start-2 focus:top-2 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2 focus:text-indigo-900">Lewati ke konten</a>

    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur">
        <nav aria-label="Navigasi utama" class="mx-auto flex w-full max-w-6xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3 text-sm">
            <a href="{{ route('beranda') }}" class="flex items-center gap-2.5 font-bold text-indigo-950">
                <x-logo-puspresma class="size-8" />
                <span class="leading-tight">Puspresma<span class="block text-[11px] font-medium text-slate-500">FKIP Universitas Siliwangi</span></span>
            </a>
            <div class="ms-auto flex flex-wrap items-center gap-x-5 gap-y-2">
                <a href="{{ route('galeri.index') }}" class="text-slate-700 hover:text-indigo-800 focus-visible:underline @if (request()->routeIs('galeri.*')) font-semibold text-indigo-800 @endif">Galeri Prestasi</a>
                <a href="{{ route('kompetisi.index') }}" class="text-slate-700 hover:text-indigo-800 focus-visible:underline @if (request()->routeIs('kompetisi.*')) font-semibold text-indigo-800 @endif">Kompetisi</a>
                <a href="{{ asset('panduan/index.html') }}" class="text-slate-700 hover:text-indigo-800 focus-visible:underline">Panduan</a>
                <a href="{{ route('login') }}" class="rounded-lg bg-indigo-700 px-4 py-2 font-semibold text-white shadow-sm transition hover:bg-indigo-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700">Masuk</a>
            </div>
        </nav>
    </header>

    <main id="konten" tabindex="-1" class="flex-1">
        @if ($penuh)
            {{ $slot }}
        @else
            <div class="mx-auto w-full max-w-6xl px-4 py-8">{{ $slot }}</div>
        @endif
    </main>

    <footer class="bg-indigo-950 text-indigo-200">
        <div class="mx-auto grid w-full max-w-6xl gap-8 px-4 py-10 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <a href="{{ route('beranda') }}" class="flex items-center gap-2.5 font-bold text-white">
                    <x-logo-puspresma class="size-9" />
                    <span>Puspresma FKIP Unsil</span>
                </a>
                <p class="mt-3 max-w-md leading-relaxed text-indigo-300">
                    Pusat Prestasi Mahasiswa Fakultas Keguruan dan Ilmu Pendidikan Universitas Siliwangi. Mencatat, memverifikasi,
                    dan melaporkan prestasi mahasiswa sebagai bukti dukung akreditasi dan penghargaan bagi mahasiswa.
                </p>
            </div>
            <div>
                <h2 class="font-semibold text-white">Jelajahi</h2>
                <ul class="mt-3 space-y-2">
                    <li><a class="hover:text-white hover:underline" href="{{ route('galeri.index') }}">Galeri Prestasi</a></li>
                    <li><a class="hover:text-white hover:underline" href="{{ route('kompetisi.index') }}">Katalog Kompetisi</a></li>
                    <li><a class="hover:text-white hover:underline" href="{{ route('login') }}">Masuk</a></li>
                </ul>
            </div>
            <div>
                <h2 class="font-semibold text-white">Bantuan</h2>
                <ul class="mt-3 space-y-2">
                    <li><a class="hover:text-white hover:underline" href="{{ asset('panduan/index.html') }}">Panduan pengguna</a></li>
                    <li><a class="hover:text-white hover:underline" href="{{ route('galeri.kebijakan') }}">Kebijakan privasi galeri</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-indigo-900 py-4 text-center text-xs text-indigo-400">
            © {{ now()->year }} Puspresma FKIP Unsil · versi {{ config('app.version') }}
        </div>
    </footer>
</body>
</html>

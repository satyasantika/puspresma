@props(['judul' => null, 'deskripsi' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul ? $judul.' · ' : '' }}{{ config('app.name') }}</title>
    @if ($deskripsi)
        <meta name="description" content="{{ $deskripsi }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-800 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <nav aria-label="Navigasi utama" class="mx-auto flex w-full max-w-6xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3 text-sm">
            <a href="{{ route('beranda') }}" class="font-semibold text-indigo-800">Puspresma FKIP Unsil</a>
            @if (Route::has('galeri.index'))
                <a href="{{ route('galeri.index') }}" class="text-slate-700 underline-offset-4 hover:underline focus-visible:underline">Galeri Prestasi</a>
            @endif
            <a href="{{ route('kompetisi.index') }}" class="text-slate-700 underline-offset-4 hover:underline focus-visible:underline">Kompetisi</a>
            <a href="{{ route('login') }}" class="ms-auto text-slate-700 underline-offset-4 hover:underline focus-visible:underline">Masuk</a>
        </nav>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 py-4 text-center text-sm text-slate-600">
        @if (Route::has('galeri.kebijakan'))
            <a href="{{ route('galeri.kebijakan') }}" class="underline-offset-4 hover:underline">Kebijakan privasi galeri</a>
            ·
        @endif
        Puspresma FKIP Unsil · versi {{ config('app.version') }}
    </footer>
</body>
</html>

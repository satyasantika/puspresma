@props(['judul' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul ? $judul.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-800 antialiased">
    <main class="mx-auto flex w-full max-w-4xl flex-1 flex-col justify-center px-4 py-12">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 py-4 text-center text-sm text-slate-500">
        Puspresma FKIP Unsil · versi {{ config('app.version') }}
    </footer>
</body>
</html>

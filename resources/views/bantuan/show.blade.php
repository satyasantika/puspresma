<x-layouts.publik :judul="'Bantuan — '.$peran">
    <nav aria-label="Panduan per peran" class="mb-6 flex flex-wrap gap-2 text-sm">
        @foreach ($daftar as $p)
            <a href="{{ route('bantuan.show', $p) }}"
               @class(['rounded px-3 py-1', 'bg-indigo-800 text-white' => $p === $peran, 'bg-white text-slate-700 ring-1 ring-slate-200' => $p !== $peran])
               @if ($p === $peran) aria-current="page" @endif>{{ Str::headline($p) }}</a>
        @endforeach
    </nav>

    <article class="max-w-3xl [&_h1]:mb-4 [&_h1]:text-2xl [&_h1]:font-bold [&_h2]:mb-2 [&_h2]:mt-6 [&_h2]:text-lg [&_h2]:font-semibold [&_li]:mb-1 [&_ol]:list-decimal [&_ol]:ps-6 [&_p]:mb-3 [&_ul]:list-disc [&_ul]:ps-6 [&_code]:rounded [&_code]:bg-slate-100 [&_code]:px-1">
        {{ $isi }}
    </article>
</x-layouts.publik>

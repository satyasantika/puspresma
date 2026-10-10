@if (count($prestasi) > 0)
    <section class="mt-10" aria-labelledby="judul-prestasi">
        <div class="flex items-baseline justify-between">
            <h2 id="judul-prestasi" class="text-xl font-semibold text-slate-900">Prestasi terbaru</h2>
            <a href="{{ route('galeri.index') }}" class="text-sm text-indigo-800 underline-offset-4 hover:underline">Lihat galeri</a>
        </div>
        <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($prestasi as $p)
                @include('galeri.kartu', ['p' => $p])
            @endforeach
        </ul>
    </section>
@endif

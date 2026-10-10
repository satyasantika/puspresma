<article class="space-y-5">
    <a href="{{ route('galeri.index') }}" class="text-sm text-indigo-800 underline-offset-4 hover:underline">← Galeri prestasi</a>

    <header class="space-y-1">
        <h1 class="text-2xl font-semibold text-slate-900">{{ $data['judul'] }}</h1>
        <p class="font-medium text-indigo-900">{{ $data['capaian'] }}@if ($data['capaian_keterangan']) — {{ $data['capaian_keterangan'] }}@endif · {{ $data['tingkat'] }}</p>
        <p class="text-slate-600">{{ $data['penyelenggara'] }} · {{ $data['tahun'] }}</p>
        @if ($data['judul_karya'])
            <p class="text-slate-700">Judul karya: <em>{{ $data['judul_karya'] }}</em></p>
        @endif
    </header>

    @if ($data['foto'] !== [])
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($data['foto'] as $foto)
                <div class="aspect-[4/3] overflow-hidden rounded-lg bg-slate-100"><x-foto-drive :url="$foto['url']" :alt="$foto['alt']" /></div>
            @endforeach
        </div>
    @endif

    @if ($data['deskripsi'])
        <p class="max-w-3xl whitespace-pre-line text-slate-700">{{ $data['deskripsi'] }}</p>
    @endif

    <section aria-label="Tim">
        <h2 class="font-semibold text-slate-900">Tim</h2>
        <ul class="mt-1 text-slate-700">
            @foreach ($data['anggota'] as $a)
                <li>{{ $a['nama'] }} <span class="text-slate-500">({{ $a['prodi'] }})</span></li>
            @endforeach
            @if ($data['anggota_lain'] > 0)
                <li class="text-slate-500">dan {{ $data['anggota_lain'] }} anggota lain</li>
            @endif
        </ul>
    </section>
</article>

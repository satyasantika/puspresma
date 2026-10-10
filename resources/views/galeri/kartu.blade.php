@props(['p'])
<li class="flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white">
    <div class="aspect-[4/3] bg-slate-100">
        @if ($p['foto'] !== [])
            <x-foto-drive :url="$p['foto'][0]['url']" :alt="$p['foto'][0]['alt']" />
        @else
            <x-foto-drive :alt="'Tanpa foto: '.$p['judul']" />
        @endif
    </div>
    <div class="flex flex-1 flex-col gap-2 p-4">
        <h2 class="font-semibold text-slate-900"><a href="{{ route('galeri.show', $p['id']) }}" class="hover:underline focus-visible:underline">{{ $p['judul'] }}</a></h2>
        <p class="text-sm font-medium text-indigo-900">{{ $p['capaian'] }} · {{ $p['tingkat'] }}</p>
        <p class="text-sm text-slate-600">{{ $p['penyelenggara'] }} · {{ $p['tahun'] }}</p>
        <ul class="mt-auto text-sm text-slate-700">
            @foreach ($p['anggota'] as $a)
                <li>{{ $a['nama'] }} <span class="text-slate-500">({{ $a['prodi'] }})</span></li>
            @endforeach
            @if ($p['anggota_lain'] > 0)
                <li class="text-slate-500">dan {{ $p['anggota_lain'] }} anggota lain</li>
            @endif
        </ul>
    </div>
</li>

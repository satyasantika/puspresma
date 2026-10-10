<article class="space-y-6">
    <a href="{{ route('kompetisi.index') }}" class="text-sm text-indigo-800 underline-offset-4 hover:underline">← Semua kompetisi</a>

    <header class="space-y-2">
        <h1 class="text-2xl font-semibold text-slate-900">{{ $kompetisi->judul }}</h1>
        <p class="text-slate-600">{{ $kompetisi->penyelenggara }}</p>
        <div class="flex flex-wrap gap-2 text-xs">
            @if ($kompetisi->tingkat) <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-indigo-900">{{ $kompetisi->tingkat->nama }}</span> @endif
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-800">{{ $kompetisi->jenisKegiatan->nama }}</span>
            @if ($ditutup) <span class="rounded-full bg-red-100 px-2 py-0.5 font-medium text-red-900">Pendaftaran ditutup</span> @endif
        </div>
    </header>

    @if ($kompetisi->poster)
        <div class="aspect-[16/9] max-w-xl overflow-hidden rounded-lg bg-slate-100">
            <x-foto-drive :tautan="$kompetisi->poster" :alt="'Poster '.$kompetisi->judul" />
        </div>
    @endif

    <dl class="grid gap-3 text-sm sm:grid-cols-2">
        <div><dt class="font-medium text-slate-700">Tenggat pendaftaran</dt><dd>{{ $kompetisi->tenggat_pendaftaran->translatedFormat('d F Y H:i') }}</dd></div>
        @if ($kompetisi->tanggal_pelaksanaan_mulai)
            <div><dt class="font-medium text-slate-700">Pelaksanaan</dt><dd>{{ $kompetisi->tanggal_pelaksanaan_mulai->translatedFormat('d F Y') }}@if ($kompetisi->tanggal_pelaksanaan_selesai) – {{ $kompetisi->tanggal_pelaksanaan_selesai->translatedFormat('d F Y') }}@endif</dd></div>
        @endif
        @if ($kompetisi->lokasi) <div><dt class="font-medium text-slate-700">Lokasi</dt><dd>{{ $kompetisi->lokasi }}</dd></div> @endif
        @if ($kompetisi->biaya_pendaftaran !== null)
            <div><dt class="font-medium text-slate-700">Biaya pendaftaran</dt><dd>{{ bccomp($kompetisi->biaya_pendaftaran, '0', 2) > 0 ? 'Rp '.number_format($kompetisi->biaya_pendaftaran, 0, ',', '.') : 'Gratis' }}</dd></div>
        @endif
        @if ($kompetisi->prodiSasaran->isNotEmpty())
            <div><dt class="font-medium text-slate-700">Sasaran prodi</dt><dd>{{ $kompetisi->prodiSasaran->pluck('nama')->implode(', ') }}</dd></div>
        @endif
    </dl>

    @if ($kompetisi->deskripsi)
        {{-- Deskripsi sudah disanitasi saat disimpan (profil purifier 'deskripsi'). --}}
        <div class="max-w-none space-y-3 [&_a]:text-indigo-800 [&_a]:underline [&_blockquote]:border-s-4 [&_blockquote]:ps-3 [&_h3]:text-lg [&_h3]:font-semibold [&_h4]:font-semibold [&_ol]:list-decimal [&_ol]:ps-6 [&_ul]:list-disc [&_ul]:ps-6">{!! $kompetisi->deskripsi !!}</div>
    @endif

    @if ($kompetisi->url_pendaftaran && ! $ditutup)
        <a href="{{ $kompetisi->url_pendaftaran }}" target="_blank" rel="noopener noreferrer"
            class="inline-flex rounded-md bg-indigo-700 px-4 py-2 font-medium text-white hover:bg-indigo-800 focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2">Daftar</a>
    @endif
    @if ($kompetisi->url_panduan)
        <a href="{{ $kompetisi->url_panduan }}" target="_blank" rel="noopener noreferrer" class="ms-3 text-indigo-800 underline-offset-4 hover:underline">Panduan</a>
    @endif
</article>

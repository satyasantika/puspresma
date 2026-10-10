<x-layouts.publik judul="Verifikasi surat keterangan prestasi">
    <article class="max-w-2xl space-y-4">
        <h1 class="text-2xl font-semibold text-slate-900">Verifikasi Surat Keterangan Prestasi</h1>

        <p @class([
            'rounded-lg px-4 py-3 font-medium',
            'bg-green-100 text-green-900' => ! $surat['dicabut'],
            'bg-red-100 text-red-900' => $surat['dicabut'],
        ])>
            Status: {{ $surat['status'] }}@if ($surat['dicabut'] && $surat['tanggal_dicabut']) sejak {{ $surat['tanggal_dicabut'] }}@endif
        </p>

        <dl class="grid gap-2 text-sm sm:grid-cols-2">
            <div><dt class="font-medium text-slate-700">Nomor</dt><dd>{{ $surat['nomor'] }}</dd></div>
            <div><dt class="font-medium text-slate-700">Tanggal terbit</dt><dd>{{ $surat['tanggal_terbit'] }}</dd></div>
            <div><dt class="font-medium text-slate-700">Nama mahasiswa</dt><dd>{{ $surat['nama'] }}</dd></div>
            <div><dt class="font-medium text-slate-700">Program studi</dt><dd>{{ $surat['prodi'] }}</dd></div>
        </dl>

        <h2 class="font-semibold text-slate-900">Prestasi yang dimuat</h2>
        <ul class="list-disc ps-5 text-slate-700">
            @foreach ($surat['prestasi'] as $p)
                <li>{{ $p['judul'] }} — {{ $p['capaian'] }} ({{ $p['tingkat'] }})</li>
            @endforeach
        </ul>
    </article>
</x-layouts.publik>

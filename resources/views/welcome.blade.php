<x-layouts.publik>
    <section class="py-6">
        <h1 class="text-3xl font-semibold text-slate-900">Puspresma FKIP Unsil</h1>
        <p class="mt-3 max-w-2xl text-lg text-slate-600">
            Pusat Prestasi Mahasiswa Fakultas Keguruan dan Ilmu Pendidikan Universitas Siliwangi:
            pencatatan, verifikasi, dan pelaporan prestasi akademik dan non-akademik mahasiswa.
        </p>
    </section>

    <section class="mt-6" aria-labelledby="judul-kompetisi">
        <div class="flex items-baseline justify-between">
            <h2 id="judul-kompetisi" class="text-xl font-semibold text-slate-900">Kompetisi terdekat</h2>
            <a href="{{ route('kompetisi.index') }}" class="text-sm text-indigo-800 underline-offset-4 hover:underline">Lihat semua</a>
        </div>
        <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($kompetisi as $k)
                <li class="rounded-lg border border-slate-200 bg-white p-4">
                    <h3 class="font-medium text-slate-900"><a href="{{ route('kompetisi.show', $k['slug']) }}" class="hover:underline">{{ $k['judul'] }}</a></h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $k['penyelenggara'] }}</p>
                    <p class="mt-2 text-sm text-slate-700">Tenggat: {{ \Illuminate\Support\Carbon::parse($k['tenggat'])->translatedFormat('d F Y') }}</p>
                </li>
            @empty
                <li class="col-span-full text-slate-600">Belum ada kompetisi yang dibuka.</li>
            @endforelse
        </ul>
    </section>

    @isset($prestasi)
        @include('galeri.sorotan', ['prestasi' => $prestasi])
    @endisset
</x-layouts.publik>

<div>
    <h1 class="text-2xl font-semibold text-slate-900">Katalog Kompetisi</h1>
    <p class="mt-1 text-slate-600">Kompetisi yang masih membuka pendaftaran, diurutkan dari tenggat terdekat.</p>

    <form class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5" role="search" aria-label="Filter kompetisi" wire:submit.prevent>
        <div class="lg:col-span-2">
            <label for="cari" class="block text-sm font-medium text-slate-700">Cari</label>
            <input id="cari" type="search" wire:model.live.debounce.400ms="q" placeholder="Judul atau penyelenggara"
                class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600">
        </div>
        <div>
            <label for="jenis" class="block text-sm font-medium text-slate-700">Jenis</label>
            <select id="jenis" wire:model.live="jenis" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-600">
                <option value="">Semua</option>
                @foreach ($opsiJenis as $id => $nama) <option value="{{ $id }}">{{ $nama }}</option> @endforeach
            </select>
        </div>
        <div>
            <label for="tingkat" class="block text-sm font-medium text-slate-700">Tingkat</label>
            <select id="tingkat" wire:model.live="tingkat" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-600">
                <option value="">Semua</option>
                @foreach ($opsiTingkat as $id => $nama) <option value="{{ $id }}">{{ $nama }}</option> @endforeach
            </select>
        </div>
        <div>
            <label for="prodi" class="block text-sm font-medium text-slate-700">Sasaran prodi</label>
            <select id="prodi" wire:model.live="prodi" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-600">
                <option value="">Semua</option>
                @foreach ($opsiProdi as $id => $nama) <option value="{{ $id }}">{{ $nama }}</option> @endforeach
            </select>
        </div>
    </form>

    <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-live="polite">
        @forelse ($hasil['items'] as $k)
            @php($sisa = (int) ceil(now()->diffInDays(\Illuminate\Support\Carbon::parse($k['tenggat']), false)))
            <li class="flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white">
                <div class="aspect-[16/9] bg-slate-100">
                    @if ($k['poster_id'])
                        <img src="https://lh3.googleusercontent.com/d/{{ $k['poster_id'] }}" alt="Poster {{ $k['judul'] }}" loading="lazy" referrerpolicy="no-referrer"
                            onerror="this.onerror=null;this.src='{{ asset('images/placeholder-prestasi.svg') }}'" class="h-full w-full object-cover">
                    @else
                        <img src="{{ asset('images/placeholder-prestasi.svg') }}" alt="" class="h-full w-full object-cover">
                    @endif
                </div>
                <div class="flex flex-1 flex-col gap-2 p-4">
                    <h2 class="font-semibold text-slate-900"><a href="{{ route('kompetisi.show', $k['slug']) }}" class="hover:underline focus-visible:underline">{{ $k['judul'] }}</a></h2>
                    <p class="text-sm text-slate-600">{{ $k['penyelenggara'] }}</p>
                    <div class="flex flex-wrap gap-2 text-xs">
                        @if ($k['tingkat']) <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-indigo-900">{{ $k['tingkat'] }}</span> @endif
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-800">{{ $k['jenis'] }}</span>
                        @if ($sisa <= 3)
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-900">Tenggat {{ max($sisa, 0) }} hari lagi</span>
                        @endif
                    </div>
                    <p class="mt-auto text-sm text-slate-700">Tenggat: {{ \Illuminate\Support\Carbon::parse($k['tenggat'])->translatedFormat('d F Y H:i') }}</p>
                </div>
            </li>
        @empty
            <li class="col-span-full rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-600">Belum ada kompetisi yang sesuai.</li>
        @endforelse
    </ul>

    @if ($hasil['terakhir'] > 1)
        <nav class="mt-6 flex items-center justify-between text-sm" aria-label="Halaman">
            <button type="button" wire:click="ke({{ $hasil['halaman'] - 1 }})" @disabled($hasil['halaman'] <= 1)
                class="rounded-md border border-slate-300 bg-white px-3 py-2 disabled:opacity-50 focus-visible:ring-2 focus-visible:ring-indigo-600">Sebelumnya</button>
            <span>Halaman {{ $hasil['halaman'] }} dari {{ $hasil['terakhir'] }}</span>
            <button type="button" wire:click="ke({{ $hasil['halaman'] + 1 }})" @disabled($hasil['halaman'] >= $hasil['terakhir'])
                class="rounded-md border border-slate-300 bg-white px-3 py-2 disabled:opacity-50 focus-visible:ring-2 focus-visible:ring-indigo-600">Berikutnya</button>
        </nav>
    @endif
</div>

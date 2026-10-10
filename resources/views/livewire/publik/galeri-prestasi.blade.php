<div>
    <h1 class="text-2xl font-semibold text-slate-900">Galeri Prestasi</h1>

    @if (! $aktif)
        <p class="mt-6 rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-600">Galeri sedang tidak tersedia.</p>
    @else
        <p class="mt-1 text-slate-600">Prestasi mahasiswa FKIP yang telah diverifikasi dan disetujui untuk dipublikasikan.</p>

        <form class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" role="search" aria-label="Filter galeri" wire:submit.prevent>
            <div>
                <label for="tahun" class="block text-sm font-medium text-slate-700">Tahun</label>
                <select id="tahun" wire:model.live="tahun" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-600">
                    <option value="">Semua</option>
                    @foreach ($opsiTahun as $t) <option value="{{ $t }}">{{ $t }}</option> @endforeach
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
                <label for="prodi" class="block text-sm font-medium text-slate-700">Prodi</label>
                <select id="prodi" wire:model.live="prodi" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-600">
                    <option value="">Semua</option>
                    @foreach ($opsiProdi as $id => $nama) <option value="{{ $id }}">{{ $nama }}</option> @endforeach
                </select>
            </div>
            <div>
                <label for="jenis" class="block text-sm font-medium text-slate-700">Jenis</label>
                <select id="jenis" wire:model.live="jenis" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-600">
                    <option value="">Semua</option>
                    @foreach ($opsiJenis as $id => $nama) <option value="{{ $id }}">{{ $nama }}</option> @endforeach
                </select>
            </div>
        </form>

        <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-live="polite">
            @forelse ($hasil['items'] as $p)
                @include('galeri.kartu', ['p' => $p])
            @empty
                <li class="col-span-full rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-600">Belum ada prestasi yang ditampilkan.</li>
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
    @endif
</div>

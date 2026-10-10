<x-filament-panels::page>
    {{ $this->form }}

    <div class="overflow-x-auto rounded-lg ring-1 ring-gray-950/10 dark:ring-white/20">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-3 py-2 text-start">#</th>
                    <th class="px-3 py-2 text-start">NPM</th>
                    <th class="px-3 py-2 text-start">Nama</th>
                    <th class="px-3 py-2 text-start">Prodi</th>
                    <th class="px-3 py-2 text-end">Prestasi</th>
                    <th class="px-3 py-2 text-end">Total poin</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->peringkat as $baris)
                    <tr class="border-t border-gray-950/5 dark:border-white/10">
                        <td class="px-3 py-2">{{ $baris['peringkat'] }}</td>
                        <td class="px-3 py-2">{{ $baris['mahasiswa']->npm }}</td>
                        <td class="px-3 py-2">{{ $baris['mahasiswa']->nama }}</td>
                        <td class="px-3 py-2">{{ $baris['mahasiswa']->prodi?->nama }}</td>
                        <td class="px-3 py-2 text-end">{{ $baris['jumlah_prestasi'] }}</td>
                        <td class="px-3 py-2 text-end font-medium">{{ $baris['total_poin'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-4 text-center text-gray-500">Belum ada poin pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>

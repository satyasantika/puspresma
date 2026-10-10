<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-lg p-4 ring-1 ring-gray-950/10 dark:ring-white/20">
            <h3 class="mb-2 text-sm font-semibold">Per tahun</h3>
            @forelse ($perTahun as $baris)
                <div class="flex justify-between text-sm"><span>{{ $baris['label'] }}</span><span class="font-medium">{{ $baris['total'] }}</span></div>
            @empty
                <p class="text-sm text-gray-500">Belum ada poin.</p>
            @endforelse
        </div>
        <div class="rounded-lg p-4 ring-1 ring-gray-950/10 dark:ring-white/20">
            <h3 class="mb-2 text-sm font-semibold">Per tahun akademik</h3>
            @forelse ($perTahunAkademik as $baris)
                <div class="flex justify-between text-sm"><span>{{ $baris['label'] }}</span><span class="font-medium">{{ $baris['total'] }}</span></div>
            @empty
                <p class="text-sm text-gray-500">Belum ada poin.</p>
            @endforelse
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg ring-1 ring-gray-950/10 dark:ring-white/20">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr><th class="px-3 py-2 text-start">Kegiatan</th><th class="px-3 py-2 text-start">Selesai</th><th class="px-3 py-2 text-start">Tingkat</th><th class="px-3 py-2 text-start">Capaian</th><th class="px-3 py-2 text-end">Poin</th></tr>
            </thead>
            <tbody>
                @foreach ($rincian as $r)
                    <tr class="border-t border-gray-950/5 dark:border-white/10">
                        <td class="px-3 py-2">{{ $r['kegiatan'] }}</td>
                        <td class="px-3 py-2">{{ $r['selesai']->translatedFormat('d M Y') }}</td>
                        <td class="px-3 py-2">{{ $r['tingkat'] }}</td>
                        <td class="px-3 py-2">{{ $r['capaian'] }}</td>
                        <td class="px-3 py-2 text-end">{{ $r['poin'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<x-filament-panels::page>
    {{ $this->form }}

    <div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200 dark:bg-amber-900/20 dark:text-amber-100">
        Format kolom resmi SIMKATMAWA perlu diverifikasi (atur di <code>config/simkatmawa.php</code>). Bila SIMKATMAWA mensyaratkan berkas
        (bukan tautan), ambil berkas dari Drive mahasiswa lewat tombol "Buka berkas" (perlu verifikasi); sistem tidak mengunduh atau membundel berkas.
    </div>

    <div class="overflow-x-auto rounded-lg ring-1 ring-gray-950/10 dark:ring-white/20">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr><th class="px-3 py-2 text-start">No. registrasi</th><th class="px-3 py-2 text-start">Kegiatan</th><th class="px-3 py-2 text-start">Tingkat</th><th class="px-3 py-2 text-start">Capaian</th><th class="px-3 py-2 text-start">Bukti</th></tr>
            </thead>
            <tbody>
                @forelse ($this->pratinjau as $baris)
                    <tr class="border-t border-gray-950/5 dark:border-white/10">
                        <td class="px-3 py-2">{{ $baris['nomor'] }}</td>
                        <td class="px-3 py-2">{{ $baris['kegiatan'] }}</td>
                        <td class="px-3 py-2">{{ $baris['tingkat'] }}</td>
                        <td class="px-3 py-2">{{ $baris['capaian'] }}</td>
                        <td class="px-3 py-2">
                            @foreach ($baris['tautan'] as $t)
                                <a href="{{ route('tautan.buka', $t['id']) }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 underline">Buka berkas ({{ $t['jenis'] }})</a><br>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-4 text-center text-gray-500">Tidak ada prestasi pada filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="text-xs text-gray-500">Pratinjau {{ count($this->pratinjau) }} dari {{ $this->totalPrestasi }} prestasi.</p>
</x-filament-panels::page>

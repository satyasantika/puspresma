<div class="space-y-4" x-data="{ aktif: 0 }">
    <h3 class="text-base font-semibold">Tautan bukti</h3>

    @forelse ($tautan as $i => $item)
        <div class="space-y-2 rounded-lg p-3 ring-1 ring-gray-950/10 dark:ring-white/20">
            <div class="flex items-center justify-between text-sm">
                <span class="font-medium">{{ $item->jenis->label() }}</span>
                @if (in_array($item->id, $dibuka, true))
                    <span class="text-xs text-green-700">✓ sudah Anda buka</span>
                @else
                    <span class="text-xs text-gray-500">belum Anda buka</span>
                @endif
            </div>
            <x-tautan-berkas :tautan="$item" />
            @if ($item->status_cek === \App\Enums\StatusCekTautan::TidakDapatDiakses)
                <p class="text-xs text-amber-700">Tautan mungkin dibagikan terbatas; buka dengan akun kampus.</p>
            @endif
            <x-pratinjau-drive :tautan="$item" />
        </div>
    @empty
        <p class="text-sm text-gray-500">Tidak ada tautan bukti yang dapat Anda lihat.</p>
    @endforelse
</div>

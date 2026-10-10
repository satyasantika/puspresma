<div class="space-y-4">
    <h3 class="text-base font-semibold">Tautan bukti</h3>

    @forelse ($tautan as $item)
        <div class="space-y-2 rounded-lg p-4 ring-1 ring-gray-950/10 dark:ring-white/20">
            <div class="text-sm font-medium">{{ $item->jenis->label() }}</div>
            <x-tautan-berkas :tautan="$item" />
            @if ($item->jenis === \App\Enums\JenisTautan::SertifikatPiagam)
                <x-pratinjau-drive :tautan="$item" />
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500">Belum ada tautan bukti.</p>
    @endforelse
</div>

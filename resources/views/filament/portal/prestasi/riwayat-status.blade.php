<div class="space-y-3">
    <h3 class="text-base font-semibold">Riwayat status</h3>

    <ol class="space-y-3 border-s border-gray-300 ps-4 dark:border-white/20">
        @foreach ($riwayat as $baris)
            <li class="text-sm">
                <div class="font-medium">
                    {{ $baris->dari_status?->label() ?? 'Dibuat' }} → {{ $baris->ke_status->label() }}
                </div>
                <div class="text-xs text-gray-500">{{ $baris->created_at->translatedFormat('d F Y H:i') }} · {{ $baris->user->name }}</div>
                @if ($baris->catatan)
                    <div class="mt-1 text-gray-700 dark:text-gray-300">{{ $baris->catatan }}</div>
                @endif
            </li>
        @endforeach
    </ol>
</div>

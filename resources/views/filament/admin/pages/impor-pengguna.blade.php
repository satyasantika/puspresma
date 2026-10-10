<x-filament-panels::page>
    <form wire:submit="impor" class="space-y-4">
        {{ $this->form }}

        <div class="flex flex-wrap gap-3">
            <x-filament::button type="button" color="gray" wire:click="periksa" icon="heroicon-o-eye">Periksa dahulu</x-filament::button>
            <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray">Impor sekarang</x-filament::button>
        </div>
    </form>

    @if ($hasil !== null)
        <div class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-900 ring-1 ring-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-100">
            <strong>{{ $hasil['dibuat'] }}</strong> akun dibuat, <strong>{{ $hasil['diubah'] }}</strong> diperbarui, <strong>{{ count($hasil['gagal']) }}</strong> gagal.
            @if ($hasil['gagal'] !== [])
                <ul class="mt-2 list-disc ps-5">
                    @foreach ($hasil['gagal'] as $g)
                        <li>Baris {{ $g['no'] }}: {{ $g['pesan'] }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    @if ($pratinjau !== [])
        <div class="overflow-x-auto rounded-lg ring-1 ring-gray-950/10 dark:ring-white/20">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-3 py-2 text-start">Baris</th><th class="px-3 py-2 text-start">Nama</th>
                        <th class="px-3 py-2 text-start">Surel</th><th class="px-3 py-2 text-start">Peran</th>
                        <th class="px-3 py-2 text-start">Prodi</th><th class="px-3 py-2 text-start">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pratinjau as $b)
                        <tr class="border-t border-gray-950/5 align-top dark:border-white/10">
                            <td class="px-3 py-2">{{ $b['no'] }}</td>
                            <td class="px-3 py-2">{{ $b['data']['nama'] }}</td>
                            <td class="px-3 py-2">{{ $b['data']['email'] }}</td>
                            <td class="px-3 py-2">{{ $b['data']['peran'] }}</td>
                            <td class="px-3 py-2">{{ $b['data']['kode_prodi'] }}</td>
                            <td class="px-3 py-2">
                                @if ($b['status'] === 'galat')
                                    <span class="font-medium text-red-600 dark:text-red-400">Galat</span>
                                    <span class="block text-xs text-red-600 dark:text-red-400">{{ implode(' ', $b['galat']) }}</span>
                                @elseif ($b['status'] === 'ubah')
                                    <span class="text-amber-600 dark:text-amber-400">Perbarui akun</span>
                                @else
                                    <span class="text-emerald-600 dark:text-emerald-400">Akun baru</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>

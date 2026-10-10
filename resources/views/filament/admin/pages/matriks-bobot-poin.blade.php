<x-filament-panels::page>
    <form wire:submit="simpan" class="space-y-6">
        {{ $this->form }}

        @if (count($this->capaian) > 0)
            <div class="overflow-x-auto rounded-lg ring-1 ring-gray-950/10 dark:ring-white/20">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-3 py-2 text-start">Capaian</th>
                            @foreach ($this->tingkat as $namaTingkat)
                                <th class="px-3 py-2 text-start">{{ $namaTingkat }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->capaian as $capaianId => $namaCapaian)
                            <tr class="border-t border-gray-950/5 dark:border-white/10">
                                <td class="px-3 py-2">{{ $namaCapaian }}</td>
                                @foreach ($this->tingkat as $tingkatId => $namaTingkat)
                                    <td class="px-3 py-1">
                                        <input type="text" inputmode="decimal" placeholder="0.00"
                                            wire:model="matriks.{{ $capaianId }}.{{ $tingkatId }}"
                                            class="w-24 rounded-md border-gray-300 text-sm dark:border-white/20 dark:bg-white/5" />
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @error('matriks') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
        @endif

        <x-filament::button type="submit">Simpan periode baru</x-filament::button>
    </form>
</x-filament-panels::page>

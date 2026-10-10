@props(['tautan'])

<div class="flex flex-wrap items-center gap-2 text-sm">
    <a href="{{ route('tautan.buka', $tautan) }}" target="_blank" rel="noopener noreferrer"
        class="inline-flex items-center rounded-md bg-primary-600 px-3 py-1.5 font-medium text-white hover:bg-primary-500">
        Buka berkas
    </a>
    <span @class([
        'rounded-full px-2 py-0.5 text-xs font-medium',
        'bg-gray-100 text-gray-700' => $tautan->status_cek->warna() === 'gray',
        'bg-green-100 text-green-800' => $tautan->status_cek->warna() === 'success',
        'bg-red-100 text-red-800' => $tautan->status_cek->warna() === 'danger',
    ])>{{ $tautan->status_cek->label() }}</span>
    <span class="text-xs text-gray-500">{{ $tautan->penyedia->label() }}</span>
    @if ($tautan->label)
        <span class="text-gray-700">{{ $tautan->label }}</span>
    @endif
</div>

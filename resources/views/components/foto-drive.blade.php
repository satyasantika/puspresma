@props(['tautan' => null, 'url' => null, 'alt' => null, 'class' => ''])

@php($placeholder = asset('images/placeholder-prestasi.svg'))
@php($sumber = $url ?? ($tautan ? app(\App\Contracts\PenyimpananBerkas::class)->urlGambar($tautan) : null))

{{-- Gambar dimuat langsung oleh browser dari Google (bukan lewat server); bila gagal, pakai placeholder lokal. --}}
<img src="{{ $sumber ?? $placeholder }}" alt="{{ $alt ?? $tautan?->label ?? 'Foto prestasi' }}"
    loading="lazy" referrerpolicy="no-referrer"
    onerror="this.onerror=null;this.src='{{ $placeholder }}'"
    {{ $attributes->merge(['class' => 'h-full w-full object-cover '.$class]) }}>

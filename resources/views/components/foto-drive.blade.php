@props(['tautan', 'alt' => null, 'class' => ''])

@php($urlGambar = app(\App\Contracts\PenyimpananBerkas::class)->urlGambar($tautan))
@php($placeholder = asset('images/placeholder-prestasi.svg'))

{{-- Gambar dimuat langsung oleh browser dari Google (bukan lewat server); bila gagal, pakai placeholder lokal. --}}
<img src="{{ $urlGambar ?? $placeholder }}" alt="{{ $alt ?? $tautan->label ?? 'Foto prestasi' }}"
    loading="lazy" referrerpolicy="no-referrer"
    onerror="this.onerror=null;this.src='{{ $placeholder }}'"
    {{ $attributes->merge(['class' => 'h-full w-full object-cover '.$class]) }}>

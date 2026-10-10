@props(['tautan'])

@php($urlPratinjau = app(\App\Contracts\PenyimpananBerkas::class)->urlPratinjau($tautan))

@if ($urlPratinjau)
    <iframe src="{{ $urlPratinjau }}" title="Pratinjau {{ $tautan->label ?? 'berkas' }}" loading="lazy"
        class="h-96 w-full rounded-md border border-gray-200" referrerpolicy="no-referrer"></iframe>
@else
    <p class="rounded-md border border-dashed border-gray-300 p-4 text-sm text-gray-500">
        Pratinjau tidak tersedia. Gunakan tombol "Buka berkas".
    </p>
@endif

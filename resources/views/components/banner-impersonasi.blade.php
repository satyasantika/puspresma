@php($asli = session('impersonator_id') ? \App\Models\User::find(session('impersonator_id')) : null)
@if ($asli)
    <div role="alert" style="position:sticky;top:0;z-index:60;display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.75rem;padding:.5rem 1rem;background:#b91c1c;color:#fff;font-size:.875rem;line-height:1.25rem">
        <span>
            <strong>Mode darurat:</strong> Anda masuk sebagai <strong>{{ auth()->user()?->name }}</strong> ({{ auth()->user()?->email }}).
            Semua tindakan dicatat atas nama {{ $asli->name }}.
        </span>
        <form method="POST" action="{{ route('impersonasi.akhiri') }}" style="margin:0">
            @csrf
            <button type="submit" style="padding:.25rem .75rem;border-radius:.375rem;background:#fff;color:#991b1b;font-weight:600;border:0;cursor:pointer">Kembali ke akun saya</button>
        </form>
    </div>
@endif

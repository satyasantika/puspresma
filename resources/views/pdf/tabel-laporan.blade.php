<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111; }
        .kop { text-align: center; border-bottom: 2px solid #111; padding-bottom: 6px; margin-bottom: 8px; }
        .kop h1 { font-size: 12px; margin: 0; }
        .kop p { margin: 1px 0; }
        h2 { font-size: 11px; margin: 6px 0 2px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #555; padding: 2px 3px; vertical-align: top; }
        th { background: #e5e7eb; }
        .kecil { font-size: 7px; color: #444; margin-top: 6px; }
    </style>
</head>
<body>
    <div class="kop">
        <h1>FAKULTAS KEGURUAN DAN ILMU PENDIDIKAN — UNIVERSITAS SILIWANGI</h1>
        <p>Pusat Prestasi Mahasiswa (Puspresma)</p>
    </div>
    <h2>{{ $judul }}</h2>
    <p>Filter: {{ $filterTeks }}</p>
    @foreach ($lembar as $bagian)
        @if (count($lembar) > 1)
            <h2>{{ $bagian['nama'] }}</h2>
        @endif
        <table>
            <thead><tr>@foreach ($bagian['header'] as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
            <tbody>
                @foreach ($bagian['baris'] as $isi)
                    <tr>@foreach ($isi as $sel)<td>{{ $sel }}</td>@endforeach</tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
    <p class="kecil">Dicetak {{ $tanggalCetak }} oleh {{ $dicetakOleh }}. @isset($catatanKaki){{ $catatanKaki }}@endisset</p>
</body>
</html>

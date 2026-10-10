<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 40px 55px; }
        body { font-family: DejaVu Serif, serif; font-size: 11px; color: #111; line-height: 1.45; }
        .kop { text-align: center; border-bottom: 3px double #111; padding-bottom: 8px; margin-bottom: 14px; }
        .kop h1 { font-size: 14px; margin: 0; }
        .kop p { margin: 1px 0; font-size: 10px; }
        h2 { text-align: center; font-size: 13px; margin: 10px 0 0; text-decoration: underline; }
        .nomor { text-align: center; margin-bottom: 12px; }
        table.data td { padding: 1px 4px; vertical-align: top; }
        table.prestasi { width: 100%; border-collapse: collapse; margin: 8px 0; }
        table.prestasi th, table.prestasi td { border: 1px solid #333; padding: 3px 5px; }
        table.prestasi th { background: #eee; }
        .ttd { margin-top: 24px; width: 100%; }
        .ttd td { vertical-align: top; }
        .watermark { position: fixed; top: 38%; left: 8%; font-size: 90px; color: rgba(200, 0, 0, .18); transform: rotate(-30deg); }
    </style>
</head>
<body>
    @if ($dicabut)
        <div class="watermark">DICABUT</div>
    @endif

    <div class="kop">
        <h1>FAKULTAS KEGURUAN DAN ILMU PENDIDIKAN</h1>
        <h1>UNIVERSITAS SILIWANGI</h1>
        <p>Pusat Prestasi Mahasiswa (Puspresma)</p>
    </div>

    <h2>SURAT KETERANGAN PRESTASI</h2>
    <p class="nomor">Nomor: {{ $surat->nomor }}</p>

    <p>Yang bertanda tangan di bawah ini menerangkan bahwa:</p>
    <table class="data">
        <tr><td>Nama</td><td>:</td><td>{{ $surat->mahasiswa->nama }}</td></tr>
        <tr><td>NPM</td><td>:</td><td>{{ $surat->mahasiswa->npm }}</td></tr>
        <tr><td>Program Studi</td><td>:</td><td>{{ $surat->mahasiswa->prodi?->nama }}</td></tr>
    </table>
    <p>
        telah mencatatkan prestasi yang terverifikasi pada sistem Puspresma FKIP Universitas Siliwangi
        @if ($surat->periode_mulai && $surat->periode_selesai)
            pada periode {{ $surat->periode_mulai->translatedFormat('d F Y') }} – {{ $surat->periode_selesai->translatedFormat('d F Y') }}
        @endif
        sebagaimana berikut:
    </p>

    <table class="prestasi">
        <thead><tr><th>No</th><th>Kegiatan</th><th>Penyelenggara</th><th>Tingkat</th><th>Capaian</th><th>Tanggal</th></tr></thead>
        <tbody>
            @foreach ($surat->prestasi as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td><td>{{ $p->nama_kegiatan }}</td><td>{{ $p->penyelenggara }}</td>
                    <td>{{ $p->tingkat->nama }}</td><td>{{ $p->capaian->nama }}</td><td>{{ $p->tanggal_selesai->translatedFormat('d F Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p>Surat keterangan ini dibuat untuk keperluan <strong>{{ $surat->keperluan }}</strong> dan dapat dipergunakan sebagaimana mestinya.</p>

    <table class="ttd">
        <tr>
            <td style="width: 45%">
                <img src="{{ $qr }}" alt="QR verifikasi" width="100" height="100"><br>
                <span style="font-size: 9px">Pindai untuk memverifikasi keaslian surat.</span>
            </td>
            <td>
                Tasikmalaya, {{ $surat->tanggal_terbit->translatedFormat('d F Y') }}<br>
                {{ $surat->penandatangan_jabatan }},<br><br><br><br>
                <strong><u>{{ $surat->penandatangan_nama }}</u></strong><br>
                @if ($surat->penandatangan_nip) NIP {{ $surat->penandatangan_nip }} @endif
            </td>
        </tr>
    </table>
</body>
</html>

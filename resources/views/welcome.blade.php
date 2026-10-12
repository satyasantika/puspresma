<x-layouts.publik :penuh="true" judul="Beranda">
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-indigo-950 via-indigo-900 to-indigo-700 text-white">
        <div aria-hidden="true" class="pointer-events-none absolute -end-24 -top-24 size-96 rounded-full bg-amber-400/20 blur-3xl"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 -start-24 size-96 rounded-full bg-indigo-400/30 blur-3xl"></div>
        <div class="relative mx-auto grid w-full max-w-6xl items-center gap-10 px-4 py-16 sm:py-20 lg:grid-cols-5">
            <div class="lg:col-span-3">
                <p class="inline-block rounded-full bg-amber-400/15 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-amber-300">Pusat Prestasi Mahasiswa · FKIP Unsil</p>
                <h1 class="mt-5 text-4xl font-bold leading-tight tracking-tight sm:text-5xl">Setiap prestasi mahasiswa, tercatat, terverifikasi, dan terbuka.</h1>
                <p class="mt-5 max-w-2xl text-lg leading-relaxed text-indigo-100">
                    Satu tempat untuk mengajukan prestasi akademik dan non-akademik, memverifikasinya secara bertahap, lalu menjadikannya
                    bukti dukung akreditasi, bahan laporan kinerja, dan kebanggaan bagi mahasiswa.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('login') }}" class="rounded-lg bg-amber-400 px-6 py-3 font-semibold text-indigo-950 shadow transition hover:bg-amber-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-300">Masuk ke Puspresma</a>
                    <a href="{{ route('galeri.index') }}" class="rounded-lg border border-white/30 px-6 py-3 font-semibold text-white transition hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Lihat galeri prestasi</a>
                </div>
            </div>

            <dl class="grid grid-cols-2 gap-4 lg:col-span-2">
                @if ($statistik['prestasi'] > 0)
                    <div class="rounded-2xl bg-white/10 p-5 ring-1 ring-white/15 backdrop-blur">
                        <dt class="text-sm text-indigo-200">Prestasi di galeri</dt>
                        <dd class="mt-1 text-4xl font-bold">{{ number_format($statistik['prestasi'], 0, ',', '.') }}</dd>
                    </div>
                @endif
                @if ($statistik['kompetisi'] > 0)
                    <div class="rounded-2xl bg-white/10 p-5 ring-1 ring-white/15 backdrop-blur">
                        <dt class="text-sm text-indigo-200">Kompetisi dibuka</dt>
                        <dd class="mt-1 text-4xl font-bold">{{ number_format($statistik['kompetisi'], 0, ',', '.') }}</dd>
                    </div>
                @endif
                <div class="col-span-2 rounded-2xl bg-white/10 p-5 ring-1 ring-white/15 backdrop-blur">
                    <dt class="text-sm text-indigo-200">Dari pengajuan hingga laporan</dt>
                    <dd class="mt-1 text-lg font-semibold leading-snug">Ajukan → Verifikasi → Terbit → Laporkan</dd>
                </div>
            </dl>
        </div>
    </section>

    <div class="mx-auto w-full max-w-6xl px-4">
        {{-- Alur --}}
        <section class="py-14" aria-labelledby="judul-alur">
            <h2 id="judul-alur" class="text-2xl font-bold text-slate-900">Bagaimana Puspresma bekerja</h2>
            <p class="mt-2 max-w-2xl text-slate-600">Empat langkah yang sama untuk setiap prestasi, dari mahasiswa hingga pimpinan fakultas.</p>
            <ol class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Ajukan', 'Mahasiswa mengisi data prestasi, menambahkan anggota tim lintas prodi, dan melampirkan tautan bukti dari Google Drive.'],
                    ['Verifikasi', 'Admin prodi lalu admin fakultas memeriksa bukti. Duplikat dideteksi otomatis dan dosen pembimbing dapat mengonfirmasi.'],
                    ['Terbit', 'Prestasi terverifikasi tampil di galeri publik bila mahasiswa setuju, lengkap dengan surat keterangan ber-QR.'],
                    ['Laporkan', 'Data menjadi dasbor, laporan kinerja, bahan akreditasi, dan ekspor untuk SIMKATMAWA.'],
                ] as $i => [$judul, $isi])
                    <li class="relative rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <span class="grid size-9 place-items-center rounded-full bg-indigo-700 text-sm font-bold text-white">{{ $i + 1 }}</span>
                        <h3 class="mt-4 font-semibold text-slate-900">{{ $judul }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $isi }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Fitur --}}
        <section class="pb-14" aria-labelledby="judul-fitur">
            <h2 id="judul-fitur" class="text-2xl font-bold text-slate-900">Yang dapat dilakukan</h2>
            <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['Prestasi tim lintas prodi', 'Prestasi dicatat sekali; semua anggota internal mendapat kredit, statistik prodi tetap akurat.'],
                    ['Bukti berupa tautan', 'Tanpa unggah berkas ke server: sertifikat dan foto cukup tautan Google Drive yang dicek keteraksesannya.'],
                    ['Verifikasi bertahap', 'Alur prodi dan fakultas dengan catatan, riwayat status, dan penguncian setelah terverifikasi.'],
                    ['Galeri berbasis persetujuan', 'Prestasi tampil publik hanya atas persetujuan mahasiswa, tanpa NPM dan tanpa tautan sertifikat.'],
                    ['Laporan dan ekspor', 'Dasbor, laporan kinerja dan IKU, ekspor Excel/PDF, serta berkas siap-lapor SIMKATMAWA.'],
                    ['Surat keterangan prestasi', 'Surat resmi ber-QR yang keasliannya dapat diperiksa siapa pun lewat halaman verifikasi.'],
                ] as [$judul, $isi])
                    <li class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="flex items-center gap-2 font-semibold text-slate-900"><span aria-hidden="true" class="size-2 rounded-full bg-amber-400"></span>{{ $judul }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $isi }}</p>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Kompetisi --}}
        <section class="pb-14" aria-labelledby="judul-kompetisi">
            <div class="flex items-baseline justify-between gap-4">
                <h2 id="judul-kompetisi" class="text-2xl font-bold text-slate-900">Kompetisi terdekat</h2>
                <a href="{{ route('kompetisi.index') }}" class="text-sm font-medium text-indigo-800 underline-offset-4 hover:underline">Lihat semua</a>
            </div>
            <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($kompetisi as $k)
                    <li class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <h3 class="font-semibold text-slate-900"><a href="{{ route('kompetisi.show', $k['slug']) }}" class="hover:text-indigo-800 hover:underline">{{ $k['judul'] }}</a></h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $k['penyelenggara'] }}</p>
                        <p class="mt-3 inline-block rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-900">Tenggat {{ \Illuminate\Support\Carbon::parse($k['tenggat'])->translatedFormat('d F Y') }}</p>
                    </li>
                @empty
                    <li class="col-span-full rounded-2xl border border-dashed border-slate-300 p-6 text-center text-slate-600">Belum ada kompetisi yang dibuka.</li>
                @endforelse
            </ul>
        </section>

        @isset($prestasi)
            @include('galeri.sorotan', ['prestasi' => $prestasi])
        @endisset

        {{-- Panduan per peran --}}
        <section class="pt-14" aria-labelledby="judul-panduan">
            <h2 id="judul-panduan" class="text-2xl font-bold text-slate-900">Panduan pengguna bergambar</h2>
            <p class="mt-2 max-w-2xl text-slate-600">Langkah demi langkah dengan tangkapan layar, sesuai peran Anda.</p>
            <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($panduan as $p)
                    <li>
                        <a href="{{ asset('panduan/'.$p['berkas']) }}" class="group block h-full rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-700">
                            <h3 class="font-semibold text-slate-900 group-hover:text-indigo-800">{{ $p['nama'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $p['ringkas'] }}</p>
                            <span class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-indigo-800">Buka panduan <span aria-hidden="true">→</span></span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <p class="mt-6 text-sm text-slate-600"><a href="{{ asset('panduan/index.html') }}" class="font-medium text-indigo-800 underline-offset-4 hover:underline">Lihat semua panduan</a></p>
        </section>
    </div>

    {{-- Ajakan --}}
    <section class="mx-auto mb-16 mt-16 w-full max-w-6xl px-4">
        <div class="rounded-3xl bg-gradient-to-r from-indigo-900 to-indigo-700 px-6 py-10 text-center text-white sm:px-12">
            <h2 class="text-2xl font-bold sm:text-3xl">Punya prestasi yang belum tercatat?</h2>
            <p class="mx-auto mt-3 max-w-xl text-indigo-100">Masuk dengan akun kampus Anda dan ajukan sekarang. Prestasi Anda berhak diakui.</p>
            <a href="{{ route('login') }}" class="mt-6 inline-block rounded-lg bg-amber-400 px-6 py-3 font-semibold text-indigo-950 shadow transition hover:bg-amber-300">Masuk ke Puspresma</a>
        </div>
    </section>
</x-layouts.publik>

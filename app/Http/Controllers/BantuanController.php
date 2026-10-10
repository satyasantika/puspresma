<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/** Panduan pengguna per peran (docs/panduan/*.md) yang dirender di dalam aplikasi; tautan "Bantuan" di kedua panel. */
class BantuanController extends Controller
{
    /** @var list<string> */
    public const PERAN = ['mahasiswa', 'dosen', 'admin-prodi', 'admin-fakultas', 'pimpinan', 'super-admin'];

    public function index(): RedirectResponse
    {
        /** @var User $pengguna */
        $pengguna = auth()->user();
        $peran = collect(self::PERAN)->first(fn (string $p): bool => $pengguna->hasRole($p)) ?? 'mahasiswa';

        return redirect()->route('bantuan.show', $peran);
    }

    public function show(string $peran): View
    {
        abort_unless(in_array($peran, self::PERAN, true), 404);

        $berkas = base_path("docs/panduan/{$peran}.md");
        abort_unless(File::exists($berkas), 404);

        return view('bantuan.show', [
            'peran' => $peran,
            'daftar' => self::PERAN,
            'isi' => new HtmlString(Str::markdown(File::get($berkas), ['html_input' => 'strip', 'allow_unsafe_links' => false])),
        ]);
    }
}

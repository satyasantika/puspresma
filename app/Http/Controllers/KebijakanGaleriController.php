<?php

namespace App\Http\Controllers;

use App\Services\Pengaturan;
use Illuminate\Contracts\View\View;

class KebijakanGaleriController extends Controller
{
    public function __invoke(): View
    {
        return view('galeri.kebijakan', ['teks' => (string) Pengaturan::ambil('teks_persetujuan_publikasi', '')]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\KatalogPublik;
use Illuminate\Contracts\View\View;

class BerandaController extends Controller
{
    /** Beranda publik: kompetisi dengan tenggat terdekat (dan prestasi terbaru dari F6.4). */
    public function __invoke(KatalogPublik $katalog): View
    {
        return view('welcome', [
            'kompetisi' => $katalog->terdekat(4),
        ]);
    }
}

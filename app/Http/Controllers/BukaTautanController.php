<?php

namespace App\Http\Controllers;

use App\Models\TautanBerkas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class BukaTautanController extends Controller
{
    /** Membuka tautan setelah Policy; pembukaan dicatat (tanpa isi) dan dialihkan ke URL asli. */
    public function __invoke(TautanBerkas $tautanBerkas): RedirectResponse
    {
        Gate::authorize('view', $tautanBerkas);

        activity('tautan')
            ->event('dibuka')
            ->performedOn($tautanBerkas)
            ->withProperties(['id' => $tautanBerkas->getKey(), 'jenis' => $tautanBerkas->jenis->value])
            ->log('Tautan berkas dibuka');

        return redirect()->away($tautanBerkas->url);
    }
}

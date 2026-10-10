<?php

namespace App\Http\Controllers;

use App\Actions\Pengguna\AkhiriImpersonasi;
use App\Actions\Pengguna\MulaiImpersonasi;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonasiController extends Controller
{
    /** Tautan bertanda tangan, berlaku 1 menit, dibuat aksi "Masuk sebagai" dan terikat pada super-admin yang menekannya. */
    public function mulai(Request $request, User $pengguna, MulaiImpersonasi $aksi): RedirectResponse
    {
        abort_unless($request->query('oleh') === (string) $request->user()->getKey(), 403);

        if (($alasan = MulaiImpersonasi::alasanDitolak($request->user(), $pengguna)) !== null) {
            return redirect()->back()->withErrors(['impersonasi' => $alasan]);
        }

        $aksi->jalankan($request->user(), $pengguna);

        $panel = $pengguna->canAccessPanel(Filament::getPanel('portal')) ? 'portal' : 'admin';

        return redirect()->to(Filament::getPanel($panel)->getUrl());
    }

    public function akhiri(AkhiriImpersonasi $aksi): RedirectResponse
    {
        abort_unless(session()->has('impersonator_id'), 404);

        return $aksi->jalankan()
            ? redirect()->to(Filament::getPanel('admin')->getUrl())
            : redirect()->route('login');
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\StatusBerkasEkspor;
use App\Models\BerkasEkspor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UnduhBerkasEksporController extends Controller
{
    /** Men-stream berkas ekspor milik pemohon dari disk tmp; 410 bila sudah kedaluwarsa/dihapus. */
    public function __invoke(BerkasEkspor $berkasEkspor): StreamedResponse
    {
        Gate::authorize('unduh', $berkasEkspor);

        abort_if(
            $berkasEkspor->status !== StatusBerkasEkspor::Siap || $berkasEkspor->kedaluwarsa() || $berkasEkspor->path === null
                || ! Storage::disk('tmp')->exists($berkasEkspor->path),
            410,
            'Berkas ekspor sudah kedaluwarsa. Silakan buat ekspor baru.',
        );

        return Storage::disk('tmp')->download($berkasEkspor->path, "{$berkasEkspor->jenis}-{$berkasEkspor->created_at->format('Ymd-His')}.{$berkasEkspor->format}");
    }
}

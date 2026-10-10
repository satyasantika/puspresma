<?php

namespace App\Console\Commands;

use App\Models\RiwayatImpor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BersihkanTmp extends Command
{
    protected $signature = 'puspresma:bersihkan-tmp';

    protected $description = 'Hapus berkas sementara di storage/app/tmp yang berumur lebih dari 24 jam (impor, berkas galat, ekspor)';

    public function handle(): int
    {
        $disk = Storage::disk('tmp');
        $batas = now()->subHours(24)->getTimestamp();
        $dihapus = 0;

        foreach ($disk->allFiles() as $berkas) {
            if (basename($berkas) !== '.gitkeep' && $disk->lastModified($berkas) < $batas) {
                $disk->delete($berkas);
                $dihapus++;
            }
        }

        // Tandai riwayat impor yang berkas galatnya sudah kedaluwarsa.
        RiwayatImpor::query()->whereNotNull('path_galat')->where('kedaluwarsa_at', '<', now())->update(['path_galat' => null]);

        $this->info("{$dihapus} berkas sementara dihapus.");

        return self::SUCCESS;
    }
}

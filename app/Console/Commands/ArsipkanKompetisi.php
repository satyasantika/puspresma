<?php

namespace App\Console\Commands;

use App\Actions\Katalog\ArsipkanKompetisiKedaluwarsa;
use Illuminate\Console\Command;

class ArsipkanKompetisi extends Command
{
    protected $signature = 'puspresma:arsipkan-kompetisi';

    protected $description = 'Arsipkan kompetisi terbit yang tenggat pendaftarannya sudah lewat (BR-23)';

    public function handle(ArsipkanKompetisiKedaluwarsa $aksi): int
    {
        $this->info($aksi->jalankan().' kompetisi diarsipkan.');

        return self::SUCCESS;
    }
}

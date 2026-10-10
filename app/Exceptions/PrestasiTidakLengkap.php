<?php

namespace App\Exceptions;

use RuntimeException;

/** Prestasi belum memenuhi syarat ajukan (BR-04); membawa daftar kekurangan. */
class PrestasiTidakLengkap extends RuntimeException
{
    /** @param  list<string>  $pesan */
    public function __construct(public readonly array $pesan)
    {
        parent::__construct('Prestasi belum lengkap: '.implode('; ', $pesan));
    }
}

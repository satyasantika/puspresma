<?php

namespace App\Exceptions;

use App\Enums\StatusPrestasi;
use RuntimeException;

/** Transisi status prestasi di luar tabel BR-07. */
class TransisiTidakSah extends RuntimeException
{
    public function __construct(public readonly StatusPrestasi $dari, public readonly StatusPrestasi $ke)
    {
        parent::__construct("Transisi status dari '{$dari->label()}' ke '{$ke->label()}' tidak sah.");
    }
}

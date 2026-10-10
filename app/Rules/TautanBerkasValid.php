<?php

namespace App\Rules;

use App\Support\AnalisisTautan;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Aturan tautan berkas (BR-06): https, domain daftar putih, bukan pemendek, bukan folder, ≤ 2048 karakter. */
class TautanBerkasValid implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('Tautan wajib diisi.');

            return;
        }

        $url = trim($value);

        if (strlen($url) > 2048) {
            $fail('Tautan terlalu panjang (maksimal 2048 karakter).');

            return;
        }

        $bagian = parse_url($url);
        if ($bagian === false || ($bagian['scheme'] ?? null) !== 'https') {
            $fail('Tautan harus diawali https://.');

            return;
        }

        if (isset($bagian['user']) || isset($bagian['pass']) || (isset($bagian['port']) && $bagian['port'] !== 443)) {
            $fail('Format tautan tidak diizinkan.');

            return;
        }

        $host = AnalisisTautan::host($url);
        if ($host === null) {
            $fail('Tautan tidak valid.');

            return;
        }

        if (AnalisisTautan::cocokDaftar($host, config('berkas.pemendek_ditolak'))) {
            $fail('Pemendek tautan tidak diizinkan; gunakan tautan asli.');

            return;
        }

        if (! AnalisisTautan::cocokDaftar($host, config('berkas.domain_diizinkan'))) {
            $fail('Domain tautan tidak diizinkan. Gunakan Google Drive atau domain unsil.ac.id.');

            return;
        }

        if (preg_match('#/(?:drive/(?:u/\d+/)?)?folders/#', (string) ($bagian['path'] ?? ''))) {
            $fail('Gunakan tautan ke satu berkas, bukan folder.');
        }
    }
}

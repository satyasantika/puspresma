<?php

namespace App\Support;

/**
 * Awalan subpath aplikasi (mis. `/puspresma` pada https://supportfkip.unsil.ac.id/puspresma) yang diturunkan dari APP_URL.
 * Reverse proxy boleh memotong awalan sebelum meneruskan (disarankan) atau meneruskannya apa adanya; keduanya didukung.
 */
class AwalanSubpath
{
    /** Awalan tanpa garis miring akhir; string kosong bila aplikasi berada di akar domain. */
    public static function dariUrl(?string $appUrl): string
    {
        $path = $appUrl === null || $appUrl === '' ? null : parse_url($appUrl, PHP_URL_PATH);

        return is_string($path) ? rtrim($path, '/') : '';
    }

    /**
     * Membuang awalan dari REQUEST_URI/PATH_INFO bila proxy meneruskannya utuh. Aman dipanggil berulang:
     * hanya segmen pertama yang persis sama dengan awalan yang dibuang.
     *
     * @param  array<string, mixed>  $server
     * @return array<string, mixed>
     */
    public static function bersihkan(array $server, string $awalan): array
    {
        if ($awalan === '') {
            return $server;
        }

        foreach (['REQUEST_URI', 'PATH_INFO'] as $kunci) {
            if (isset($server[$kunci]) && is_string($server[$kunci])) {
                $server[$kunci] = self::buang($server[$kunci], $awalan);
            }
        }

        return $server;
    }

    private static function buang(string $uri, string $awalan): string
    {
        if ($uri === $awalan) {
            return '/';
        }

        if (str_starts_with($uri, $awalan.'/') || str_starts_with($uri, $awalan.'?')) {
            $sisa = substr($uri, strlen($awalan));

            return str_starts_with($sisa, '?') ? '/'.$sisa : $sisa;
        }

        return $uri;
    }
}

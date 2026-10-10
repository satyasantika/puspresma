<?php

namespace App\Support;

use App\Enums\PenyediaTautan;

/** Analisis murni (tanpa I/O) atas URL tautan berkas: host, penyedia, ID Drive, dan bentuk normal. */
class AnalisisTautan
{
    private const POLA_ID_DRIVE = [
        '#/file/d/([A-Za-z0-9_-]+)#',
        '#/(?:document|spreadsheets|presentation|forms)/d/([A-Za-z0-9_-]+)#',
    ];

    public static function host(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? strtolower(rtrim($host, '.')) : null;
    }

    /**
     * Apakah $host cocok dengan salah satu pola daftar ("*.unsil.ac.id" = subdomain, selain itu persis).
     *
     * @param  list<string>  $pola
     */
    public static function cocokDaftar(string $host, array $pola): bool
    {
        foreach ($pola as $item) {
            $item = strtolower($item);
            if (str_starts_with($item, '*.')) {
                if (str_ends_with($host, substr($item, 1)) && strlen($host) > strlen($item) - 1) {
                    return true;
                }
            } elseif ($host === $item) {
                return true;
            }
        }

        return false;
    }

    public static function penyedia(string $url): PenyediaTautan
    {
        $host = self::host($url) ?? '';

        return match (true) {
            $host === 'drive.google.com' => PenyediaTautan::GoogleDrive,
            $host === 'docs.google.com' => PenyediaTautan::GoogleDocs,
            $host === 'onedrive.live.com', str_ends_with($host, '.sharepoint.com') => PenyediaTautan::Onedrive,
            str_ends_with($host, '.unsil.ac.id') => PenyediaTautan::Unsil,
            default => PenyediaTautan::Lainnya,
        };
    }

    /** ID berkas Google dari /file/d/<id>, open?id=<id>, uc?id=<id>, dan /document|spreadsheets|presentation/d/<id>. */
    public static function driveFileId(string $url): ?string
    {
        if (! in_array(self::host($url), ['drive.google.com', 'docs.google.com'], true)) {
            return null;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        foreach (self::POLA_ID_DRIVE as $pola) {
            if (preg_match($pola, $path, $cocok)) {
                return $cocok[1];
            }
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        if (in_array($path, ['/open', '/uc'], true) && isset($query['id']) && preg_match('/^[A-Za-z0-9_-]+$/', (string) $query['id'])) {
            return (string) $query['id'];
        }

        return null;
    }

    /** Host huruf kecil; tanpa fragmen, usp=sharing, dan parameter utm_*. */
    public static function normal(string $url): string
    {
        $bagian = parse_url(trim($url));
        if ($bagian === false || ! isset($bagian['host'])) {
            return trim($url);
        }

        parse_str($bagian['query'] ?? '', $query);
        foreach (array_keys($query) as $kunci) {
            if ($kunci === 'usp' || str_starts_with(strtolower((string) $kunci), 'utm_')) {
                unset($query[$kunci]);
            }
        }

        return strtolower($bagian['scheme'] ?? 'https').'://'.strtolower($bagian['host'])
            .(isset($bagian['port']) ? ':'.$bagian['port'] : '')
            .($bagian['path'] ?? '')
            .($query === [] ? '' : '?'.http_build_query($query));
    }
}

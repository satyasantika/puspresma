<?php

namespace App\Support;

/** Normalisasi nomor WhatsApp Indonesia ke format 62xxxxxxxxxx. */
class NomorWhatsApp
{
    public static function normalisasi(?string $nomor): ?string
    {
        $digit = preg_replace('/\D+/', '', (string) $nomor) ?? '';
        if ($digit === '') {
            return null;
        }

        $digit = match (true) {
            str_starts_with($digit, '62') => $digit,
            str_starts_with($digit, '0') => '62'.substr($digit, 1),
            str_starts_with($digit, '8') => '62'.$digit,
            default => $digit,
        };

        return self::valid($digit) ? $digit : null;
    }

    public static function valid(?string $nomor): bool
    {
        return $nomor !== null && preg_match('/^62[1-9]\d{7,12}$/', $nomor) === 1;
    }

    /** Empat digit terakhir untuk log (nomor lengkap tidak dicatat). */
    public static function samarkan(string $nomor): string
    {
        return '…'.substr($nomor, -4);
    }
}

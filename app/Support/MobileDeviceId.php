<?php

namespace App\Support;

/**
 * Normalise l’identifiant appareil envoyé par le client mobile / PWA.
 * Safari iOS envoie souvent le User-Agent entier (> 128 car.) → rejet validation.max.string.
 */
final class MobileDeviceId
{
    public static function normalize(?string $raw): string
    {
        $value = trim((string) $raw);
        if ($value === '') {
            return '';
        }

        if (strlen($value) <= 128 && ! self::looksLikeUserAgent($value)) {
            return $value;
        }

        // Empreinte stable ≤ 128 car. (colonnes devices / pointrust_devices).
        return 'web_'.hash('sha256', $value);
    }

    public static function looksLikeUserAgent(string $value): bool
    {
        $lower = strtolower($value);

        return str_contains($lower, 'mozilla/')
            || str_contains($lower, 'applewebkit')
            || str_contains($lower, 'mobile/')
            || str_contains($lower, 'safari/');
    }
}

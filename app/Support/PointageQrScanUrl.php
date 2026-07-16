<?php

namespace App\Support;

/**
 * QR « scannable » : contenu = URL HTTPS vers /mobile/pointage/scan?…
 * (redirection app mobile + repli web).
 */
final class PointageQrScanUrl
{
    public static function baseUrl(): string
    {
        $custom = trim((string) config('pointage.qr_scan_base_url', ''));

        return $custom !== ''
            ? rtrim($custom, '/')
            : rtrim((string) url('/mobile/pointage/scan'), '/');
    }

    public static function forPointageToken(string $token): string
    {
        return self::baseUrl().'?t='.rawurlencode($token);
    }

    public static function forPointrustPayload(string $payload): string
    {
        return self::baseUrl().'?q='.rawurlencode($payload);
    }

    public static function encodeAsUrl(): bool
    {
        return (bool) config('pointage.qr_encode_as_url', true);
    }

    /**
     * Extrait le jeton / payload si l’utilisateur a scanné une URL complète.
     */
    public static function normalizeScannedContent(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        if (! filter_var($raw, FILTER_VALIDATE_URL)) {
            return $raw;
        }

        $query = parse_url($raw, PHP_URL_QUERY);
        if (! is_string($query) || $query === '') {
            return $raw;
        }

        parse_str($query, $params);

        foreach (['q', 't', 'token', 'qr_payload', 'qrPayload'] as $key) {
            if (! empty($params[$key]) && is_string($params[$key])) {
                return $params[$key];
            }
        }

        return $raw;
    }

    public static function appDeepLink(?string $t = null, ?string $q = null): ?string
    {
        $scheme = trim((string) config('pointage.mobile_app_scheme', 'cofipointe'));
        if ($scheme === '') {
            return null;
        }

        $query = http_build_query(array_filter([
            't' => $t,
            'q' => $q,
        ], static fn ($v) => $v !== null && $v !== ''));

        return $scheme.'://pointage/scan'.($query !== '' ? '?'.$query : '');
    }
}

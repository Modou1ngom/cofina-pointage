<?php

namespace App\Services\Pointrust;

final class PointrustQrPayloadService
{
    public static function sign(string $sessionId, int $timestamp, string $secret): string
    {
        return hash_hmac('sha256', $sessionId.'|'.$timestamp, $secret);
    }

    public static function buildPayload(string $sessionId, int $timestamp, string $secret): string
    {
        return $sessionId.'|'.$timestamp.'|'.self::sign($sessionId, $timestamp, $secret);
    }

    /**
     * @return array{0: string, 1: int, 2: string}|null
     */
    public static function parse(string $qrPayload): ?array
    {
        $parts = explode('|', $qrPayload, 3);
        if (count($parts) !== 3) {
            return null;
        }
        if (! ctype_digit($parts[1])) {
            return null;
        }

        return [$parts[0], (int) $parts[1], $parts[2]];
    }

    public static function verifySignature(string $sessionId, int $timestamp, string $signature, string $secret): bool
    {
        return hash_equals(self::sign($sessionId, $timestamp, $secret), $signature);
    }
}

<?php

namespace App\Services;

use App\Models\Agence;
use App\Models\PointrustQrSession;
use App\Models\User;
use App\Services\Pointrust\PointrustQrPayloadService;
use App\Support\PointageQrScanUrl;
use Carbon\Carbon;

/**
 * Résout le site (agence) à partir d’un QR scanné : session POINTRUST ou jeton site pointage.
 */
final class PointageQrScanResolver
{
    public function __construct(
        private readonly PointageQrService $pointageQr,
    ) {}

    /**
     * @return array{agence: Agence, qr_kind: 'pointrust_session'|'pointage_site', session?: PointrustQrSession}|null
     */
    public function resolve(string $rawQr, ?User $user = null): ?array
    {
        $content = PointageQrScanUrl::normalizeScannedContent(trim($rawQr));
        if ($content === '') {
            return null;
        }

        $fromPointrust = $this->resolvePointrustSession($content);
        if ($fromPointrust !== null) {
            return $fromPointrust;
        }

        $agence = $this->resolvePointageSiteToken($content, $user);
        if ($agence === null) {
            return null;
        }

        return [
            'agence' => $agence,
            'qr_kind' => 'pointage_site',
        ];
    }

    /**
     * @return array{agence: Agence, qr_kind: 'pointrust_session', session: PointrustQrSession}|null
     */
    private function resolvePointrustSession(string $content): ?array
    {
        $parsed = PointrustQrPayloadService::parse($content);
        if ($parsed === null) {
            return null;
        }

        [$sessionId, $timestamp, $signature] = $parsed;
        $secret = (string) config('pointrust.jwt_secret');
        if (! PointrustQrPayloadService::verifySignature($sessionId, $timestamp, $signature, $secret)) {
            return null;
        }

        $maxSkew = (int) config('pointrust.qr_ttl_seconds', 120) + 45;
        if (abs(time() - $timestamp) > $maxSkew) {
            return null;
        }

        $session = PointrustQrSession::query()->with('agence')->find($sessionId);
        if ($session === null || $session->status !== 'pending') {
            return null;
        }

        if (Carbon::now()->greaterThan($session->expires_at)) {
            $session->update(['status' => 'expired']);

            return null;
        }

        $agence = $session->agence;
        if ($agence === null || ! $agence->actif) {
            return null;
        }

        return [
            'agence' => $agence,
            'qr_kind' => 'pointrust_session',
            'session' => $session,
        ];
    }

    private function resolvePointageSiteToken(string $token, ?User $user): ?Agence
    {
        $decoded = $this->decodePointageTokenPayload($token);
        if ($decoded === null) {
            return null;
        }

        $agenceId = (int) ($decoded['aid'] ?? 0);
        if ($agenceId <= 0) {
            return null;
        }

        $agence = Agence::query()->find($agenceId);
        if ($agence === null || ! $agence->actif || ! $agence->isEnrolledForPointageQr()) {
            return null;
        }

        if (($decoded['exp'] ?? 0) < Carbon::now()->timestamp) {
            return null;
        }

        $version = (int) ($decoded['v'] ?? 1);
        if ($version >= 2 && $user !== null) {
            if (! $this->pointageQr->verifyToken($token, $agence, $user)) {
                return null;
            }
        } elseif ($version >= 2) {
            return null;
        } else {
            if (! $this->verifySiteTokenSignature($token, $agence, $decoded)) {
                return null;
            }
        }

        return $agence;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodePointageTokenPayload(string $token): ?array
    {
        $decoded = base64_decode(strtr($token, '-_', '+/'), true);
        if ($decoded === false || ! str_contains($decoded, '|')) {
            return null;
        }

        [$body, $sig] = explode('|', $decoded, 2);

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        $data['_body'] = $body;
        $data['_sig'] = $sig;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private function verifySiteTokenSignature(string $token, Agence $agence, array $decoded): bool
    {
        $secret = $this->pointageQr->ensureSecret($agence);
        $body = (string) ($decoded['_body'] ?? '');
        $sig = (string) ($decoded['_sig'] ?? '');
        $expected = hash_hmac('sha256', $body, $secret);

        return hash_equals($expected, $sig);
    }
}

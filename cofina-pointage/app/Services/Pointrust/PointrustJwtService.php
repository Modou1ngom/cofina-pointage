<?php

namespace App\Services\Pointrust;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

final class PointrustJwtService
{
    private function secret(): string
    {
        return (string) config('pointrust.jwt_secret');
    }

    public function issueAccessToken(User $user): string
    {
        $now = time();

        return JWT::encode([
            'sub' => (string) $user->id,
            'typ' => 'access',
            'iat' => $now,
            'exp' => $now + (int) config('pointrust.access_ttl_seconds'),
        ], $this->secret(), 'HS256');
    }

    public function issueRefreshToken(User $user): string
    {
        $now = time();

        return JWT::encode([
            'sub' => (string) $user->id,
            'typ' => 'refresh',
            'iat' => $now,
            'exp' => $now + (int) config('pointrust.refresh_ttl_seconds'),
        ], $this->secret(), 'HS256');
    }

    /**
     * @return object{sub: string, typ: string, iat: int, exp: int}
     */
    public function decode(string $jwt, string $expectedTyp = 'access'): object
    {
        $decoded = JWT::decode($jwt, new Key($this->secret(), 'HS256'));
        if (($decoded->typ ?? '') !== $expectedTyp) {
            throw new SignatureInvalidException('Invalid token type');
        }

        return $decoded;
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Pointrust\PointrustJwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PointrustAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if ($token === null || $token === '') {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            $decoded = app(PointrustJwtService::class)->decode($token, 'access');
            $user = User::query()->find((int) $decoded->sub);
            if (! $user || ! $user->is_active) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $request->setUserResolver(static fn () => $user);

            return $next($request);
        } catch (\Throwable) {
            return response()->json(['message' => 'Invalid or expired token'], 401);
        }
    }
}

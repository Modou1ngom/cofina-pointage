<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectOtpPendingSanctumToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        $token = $user->currentAccessToken();
        if ($token === null) {
            return $next($request);
        }

        $abilities = $token->abilities ?? [];
        if (in_array('*', $abilities, true)) {
            return $next($request);
        }

        if (in_array('otp-pending', $abilities, true)) {
            return response()->json([
                'message' => 'Validation OTP requise avant d’utiliser cette ressource.',
            ], 403);
        }

        return $next($request);
    }
}

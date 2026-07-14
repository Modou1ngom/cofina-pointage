<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accès web au module « Pointage & Présence » : rôle RH uniquement
 * (le super admin conserve l’accès pour support / cohérence avec CheckRole).
 */
class EnsureRhPointageWebAccess
{
    /**
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if ($user->isRh()) {
            return $next($request);
        }

        abort(403, 'Le module Pointage & Présence est réservé au profil RH.');
    }
}

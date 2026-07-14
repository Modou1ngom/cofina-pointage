<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Page HTML légère pour WebView mobile : connexion via POST /api/login (JWT PointRust).
 * URL : GET /mobile/pointrust/login
 */
class MobilePointrustWebLoginController extends Controller
{
    public function show(): View
    {
        if (! config('pointrust.mobile_web_login_enabled', true)) {
            abort(404);
        }

        return view('mobile.pointrust-login', [
            'apiBaseUrl' => rtrim((string) url('/api'), '/'),
            'nativeScheme' => config('pointrust.mobile_native_scheme'),
        ]);
    }
}

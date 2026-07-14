<?php

namespace App\Http\Controllers;

use App\Support\PointageQrScanUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Page publique ouverte lors du scan d’un QR site (URL HTTPS).
 */
class MobilePointageScanController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $t = trim((string) $request->query('t', ''));
        $q = trim((string) $request->query('q', ''));

        $deepLink = PointageQrScanUrl::appDeepLink(
            $t !== '' ? $t : null,
            $q !== '' ? $q : null,
        );

        if ($deepLink !== null && $request->boolean('open_app')) {
            return redirect()->away($deepLink);
        }

        return view('mobile.pointage-scan', [
            'token' => $t !== '' ? $t : null,
            'pointrustPayload' => $q !== '' ? $q : null,
            'deepLink' => $deepLink,
            'webPointerUrl' => $t !== ''
                ? route('pointage.pointer').'?qr_token='.rawurlencode($t)
                : null,
        ]);
    }
}

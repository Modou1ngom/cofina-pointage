<?php

namespace App\Http\Controllers\Api\Pointrust;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Models\PointrustQrSession;
use App\Services\Pointrust\PointrustQrPayloadService;
use App\Support\PointageQrScanUrl;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QrController extends Controller
{
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agence_id' => 'nullable|integer|exists:agences,id',
        ]);

        $admin = $request->user();
        $agenceId = $validated['agence_id'] ?? null;
        $agence = null;
        if ($agenceId) {
            $agence = Agence::query()->where('id', $agenceId)->where('actif', true)->first();
        }
        if (! $agence) {
            $ids = $admin->agences()->pluck('id');
            $agence = Agence::query()
                ->whereIn('id', $ids)
                ->where('actif', true)
                ->orderBy('nom')
                ->first()
                ?? Agence::query()->where('actif', true)->orderBy('nom')->first();
        }

        if (! $agence) {
            return response()->json(['message' => 'Aucun site actif disponible pour générer un QR'], 422);
        }

        $sessionId = 'sess_'.Str::lower(Str::random(20));
        $ttl = (int) config('pointrust.qr_ttl_seconds');
        $issued = time();
        $expiresAt = Carbon::createFromTimestamp($issued + $ttl);

        PointrustQrSession::query()->create([
            'id' => $sessionId,
            'agence_id' => $agence->id,
            'created_by_user_id' => $admin->id,
            'status' => 'pending',
            'expires_at' => $expiresAt,
        ]);

        $secret = (string) config('pointrust.jwt_secret');
        $payload = PointrustQrPayloadService::buildPayload($sessionId, $issued, $secret);
        $scanUrl = PointageQrScanUrl::forPointrustPayload($payload);
        $qrContent = PointageQrScanUrl::encodeAsUrl() ? $scanUrl : $payload;

        return response()->json([
            'session_id' => $sessionId,
            'qr_payload' => $payload,
            'scan_url' => $scanUrl,
            'qr_content' => $qrContent,
            'expires_at' => $expiresAt->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
        ]);
    }
}

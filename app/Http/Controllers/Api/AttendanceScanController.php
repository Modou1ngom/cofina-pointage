<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AttendanceScanRequest;
use App\Services\PointageQrScanResolver;
use App\Support\PointageEnrolment;
use App\Support\PointageGeofencing;
use App\Support\PointageJourSemaine;
use Illuminate\Http\JsonResponse;

/**
 * Validation du scan QR + position GPS (géorepérage) avant pointage entrée/sortie.
 */
class AttendanceScanController extends Controller
{
    public function __construct(
        private readonly PointageQrScanResolver $qrScanResolver,
    ) {}

    public function validateScan(AttendanceScanRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        if (PointageJourSemaine::isJourQrInactif()) {
            return response()->json([
                'message' => PointageJourSemaine::messageQrInactif(),
                'error' => 'qr_inactif_jour',
                'valid' => false,
                'window' => PointageJourSemaine::windowInfo(),
            ], 422);
        }

        $resolved = $this->qrScanResolver->resolve($request->qrContent(), $user);
        if ($resolved === null) {
            return response()->json([
                'message' => 'QR Code invalide ou expiré',
                'error' => 'invalid_qr',
                'valid' => false,
            ], 422);
        }

        $agence = $resolved['agence'];

        if (! ($agence->pointage_qr_enabled ?? true)) {
            return response()->json([
                'message' => 'Le QR Code de ce site est temporairement désactivé',
                'error' => 'qr_disabled',
                'valid' => false,
            ], 422);
        }

        $enrolment = PointageEnrolment::ensureAuthorized($user, $agence);
        if (! $enrolment['ok']) {
            return response()->json([
                'message' => $enrolment['message'],
                'error' => $enrolment['reason'] ?? 'not_enrolled',
                'valid' => false,
                'agence' => [
                    'id' => $agence->id,
                    'nom' => $agence->nom,
                ],
            ], 403);
        }

        $geo = PointageGeofencing::validate(
            $agence,
            (float) $validated['latitude'],
            (float) $validated['longitude'],
        );

        if (! $geo['ok']) {
            return response()->json(array_merge(
                PointageGeofencing::toJsonError($geo),
                ['valid' => false],
            ), 422);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Scan valide — position GPS de l’appareil acceptée pour ce site.',
            'qr_kind' => $resolved['qr_kind'],
            'position_source' => 'device_gps',
            'agence' => [
                'id' => $agence->id,
                'nom' => $agence->nom,
                'code_agent' => $agence->code_agent,
            ],
            'office_zone' => [
                'agence_id' => $agence->id,
                'agence_nom' => $agence->nom,
                'latitude' => $geo['site_latitude'] ?? null,
                'longitude' => $geo['site_longitude'] ?? null,
                'radius_metres' => (int) ($geo['rayon_autorise_metres'] ?? 50),
            ],
            'geofencing' => [
                'distance_metres' => $geo['distance_metres'] ?? null,
                'rayon_autorise_metres' => $geo['rayon_autorise_metres'] ?? null,
                'site_latitude' => $geo['site_latitude'] ?? null,
                'site_longitude' => $geo['site_longitude'] ?? null,
                'scan_latitude' => $geo['scan_latitude'] ?? null,
                'scan_longitude' => $geo['scan_longitude'] ?? null,
            ],
        ]);
    }
}

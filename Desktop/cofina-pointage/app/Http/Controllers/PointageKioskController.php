<?php

namespace App\Http\Controllers;

use App\Models\Agence;
use App\Services\PointageQrService;
use App\Support\PointageJourSemaine;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Affichage borne / tablette : QR site scannable par les employés (app mobile).
 * Accès via jeton secret d’agence (pas de session RH sur la tablette).
 */
class PointageKioskController extends Controller
{
    public function show(string $token, PointageQrService $qr): Response
    {
        $resolved = $this->resolveAgence($token);

        if ($resolved['error'] !== null) {
            return Inertia::render('Pointage/Kiosk', [
                'agence' => $resolved['agence'] ? [
                    'id' => $resolved['agence']->id,
                    'nom' => $resolved['agence']->nom,
                    'code_agent' => $resolved['agence']->code_agent,
                    'pointage_qr_type' => $resolved['agence']->pointage_qr_type ?? 'dynamic',
                    'rayon_geofencing_metres' => $resolved['agence']->rayon_geofencing_metres ?? 50,
                ] : null,
                'qr' => null,
                'refresh_url' => null,
                'kiosk_unavailable' => true,
                'unavailable_message' => $resolved['error'],
                'qr_inactif_jour' => false,
                'qr_inactif_message' => null,
            ]);
        }

        $agence = $resolved['agence'];
        $minted = $qr->mintToken($agence);

        return Inertia::render('Pointage/Kiosk', [
            'agence' => [
                'id' => $agence->id,
                'nom' => $agence->nom,
                'code_agent' => $agence->code_agent,
                'pointage_qr_type' => $agence->pointage_qr_type ?? 'dynamic',
                'rayon_geofencing_metres' => $agence->rayon_geofencing_metres ?? 50,
            ],
            'qr' => $minted,
            'refresh_url' => route('pointage.kiosk.qr', ['token' => $agence->pointage_kiosk_token]),
            'kiosk_unavailable' => false,
            'unavailable_message' => null,
            'qr_inactif_jour' => PointageJourSemaine::isJourQrInactif(),
            'qr_inactif_message' => PointageJourSemaine::isJourQrInactif()
                ? PointageJourSemaine::messageQrInactif()
                : null,
        ]);
    }

    public function refresh(string $token, PointageQrService $qr): JsonResponse
    {
        $resolved = $this->resolveAgence($token);

        if ($resolved['error'] !== null) {
            return response()->json([
                'ok' => false,
                'error' => 'kiosk_unavailable',
                'message' => $resolved['error'],
            ], 403);
        }

        $agence = $resolved['agence'];

        if (PointageJourSemaine::isJourQrInactif()) {
            return response()->json([
                'ok' => false,
                'error' => 'qr_inactif_jour',
                'message' => PointageJourSemaine::messageQrInactif(),
            ], 422);
        }

        $minted = $qr->mintToken($agence);

        return response()->json([
            'ok' => true,
            'qr' => $minted,
        ]);
    }

    /**
     * @return array{agence: Agence|null, error: string|null}
     */
    private function resolveAgence(string $token): array
    {
        $token = trim($token);
        abort_if($token === '' || strlen($token) < 16, 404);

        $agence = Agence::query()
            ->where('pointage_kiosk_token', $token)
            ->first();

        abort_if($agence === null, 404);
        abort_unless($agence->isEnrolledForPointageQr(), 404);

        if (! $agence->actif) {
            return [
                'agence' => $agence,
                'error' => 'Cette agence est inactive. Réactivez-la dans Pointage → Sites.',
            ];
        }

        if (! ($agence->pointage_qr_enabled ?? true)) {
            return [
                'agence' => $agence,
                'error' => 'Le QR de cette agence est en pause. Dans Pointage → Sites, cliquez sur « Activer QR ».',
            ];
        }

        return [
            'agence' => $agence,
            'error' => null,
        ];
    }
}

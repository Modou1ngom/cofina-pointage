<?php

namespace App\Support;

use App\Models\Agence;
use App\Models\User;

/**
 * Zone de référence (site) pour le géorepérage côté app mobile.
 * La position de scan doit être lue sur l’appareil (GPS), pas saisie à la main.
 */
final class MobileApiGeolocation
{
    /**
     * @return array<string, mixed>|null
     */
    public static function officeZoneForUser(User $user): ?array
    {
        $user->loadMissing(['agences', 'profil']);
        $agence = $user->agences->firstWhere('pivot.is_default', true)
            ?? $user->agences->first();

        if ($agence === null && $user->profil?->site) {
            $agence = Agence::query()->where('nom', $user->profil->site)->where('actif', true)->first();
        }

        if ($agence === null) {
            return null;
        }

        if ($agence->latitude === null || $agence->longitude === null) {
            return [
                'agence_id' => $agence->id,
                'agence_nom' => $agence->nom,
                'latitude' => null,
                'longitude' => null,
                'radius_metres' => (int) ($agence->rayon_geofencing_metres ?? config('pointage.default_geofencing_radius_metres', 50)),
                'configured' => false,
            ];
        }

        return [
            'agence_id' => $agence->id,
            'agence_nom' => $agence->nom,
            'latitude' => (float) $agence->latitude,
            'longitude' => (float) $agence->longitude,
            'radius_metres' => (int) ($agence->rayon_geofencing_metres ?? config('pointage.default_geofencing_radius_metres', 50)),
            'configured' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function clientHints(): array
    {
        return [
            'position_source' => 'device_gps',
            'positionSource' => 'device_gps',
            'manual_coordinates_allowed' => false,
            'manualCoordinatesAllowed' => false,
            'message' => 'Au scan du QR, envoyez latitude et longitude lues sur l’appareil (GPS activé).',
        ];
    }
}

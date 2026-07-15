<?php

namespace App\Support;

use App\Models\Profil;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Payload « Mon compte » mobile : champs affichés dans l’écran profil Flutter.
 */
final class MobileApiAccountResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(User $user, ?Request $request = null): array
    {
        $user->profilCollaborateurAssocie();
        $deviceSerial = $request !== null
            ? MobileDeviceSerialResolver::fromRequest($request, $user)
            : MobileDeviceSerialResolver::forUser($user);

        /** @var Profil|null $p */
        $p = $user->profil;

        $prenom = trim((string) ($p?->prenom ?? ''));
        $nom = trim((string) ($p?->nom ?? ''));
        $fullName = trim($prenom.' '.$nom);
        if ($fullName === '') {
            $fullName = trim((string) ($user->full_name ?? $user->name ?? ''));
        }

        $departement = trim((string) ($p?->departement ?? ''));
        $fonction = trim((string) ($p?->fonction ?? ''));
        $service = $departement !== '' ? $departement : $fonction;

        $telephone = trim((string) ($p?->telephone ?? ''));
        $matricule = trim((string) ($user->matricule ?? $p?->matricule ?? ''));

        $officeZone = MobileApiGeolocation::officeZoneForUser($user);
        $geoHints = MobileApiGeolocation::clientHints();

        $payload = [
            'id' => $user->id,
            'full_name' => $fullName,
            'email' => (string) $user->email,
            'matricule' => $matricule !== '' ? $matricule : null,
            /** N° de série appareil (1ʳᵉ connexion), pas une URL d’image. */
            'avatar_url' => $deviceSerial,
            /** Champ explicite pour l’écran Flutter « Appareil lié ». */
            'linked_device' => $deviceSerial,
            'appareil_lie' => $deviceSerial,
            'departement' => $departement !== '' ? $departement : null,
            'service' => $service !== '' ? $service : null,
            'telephone' => $telephone !== '' ? $telephone : null,
            'office_zone' => $officeZone,
            'officeZone' => $officeZone,
            'geolocation' => $geoHints,
        ];

        return array_merge($payload, self::aliases($payload));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function aliases(array $data): array
    {
        return [
            'fullName' => $data['full_name'],
            'avatarUrl' => $data['avatar_url'],
            'linkedDevice' => $data['linked_device'],
            'appareilLie' => $data['appareil_lie'],
            'department' => $data['departement'],
            'phone' => $data['telephone'],
            'telephon' => $data['telephone'],
        ];
    }
}

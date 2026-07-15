<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Politique jours de la semaine pour le pointage :
 *  - QR_INACTIF : QR Code totalement désactivé (ex. dimanche). Aucun pointage possible.
 *  - QR_OPTIONNEL : QR actif, mais le pointage n'est pas obligatoire (ex. samedi).
 *                   Les absences ne sont pas comptées ; seuls les staffs qui pointent sont enregistrés.
 *
 * Configurable via :
 *  - POINTAGE_QR_INACTIF_JOURS (CSV de jours, 0=dimanche … 6=samedi). Défaut : 0
 *  - POINTAGE_QR_OPTIONNEL_JOURS (CSV idem). Défaut : 6
 */
final class PointageJourSemaine
{
    /**
     * @return list<int>
     */
    public static function joursQrInactifs(): array
    {
        return self::parseDows((string) config('pointage.qr_inactif_jours', '0'), [0]);
    }

    /**
     * @return list<int>
     */
    public static function joursQrOptionnels(): array
    {
        return self::parseDows((string) config('pointage.qr_optionnel_jours', '6'), [6]);
    }

    public static function isJourQrInactif(?Carbon $d = null): bool
    {
        $d = $d ?? Carbon::now();

        return in_array((int) $d->dayOfWeek, self::joursQrInactifs(), true);
    }

    public static function isJourPointageOptionnel(?Carbon $d = null): bool
    {
        $d = $d ?? Carbon::now();

        return in_array((int) $d->dayOfWeek, self::joursQrOptionnels(), true);
    }

    public static function messageQrInactif(?Carbon $d = null): string
    {
        $d = $d ?? Carbon::now();
        $libelle = self::libelleJour((int) $d->dayOfWeek);

        return "QR Code désactivé le {$libelle}. Aucun pointage n'est requis ce jour-là.";
    }

    /**
     * Indication remontée à l'app mobile / écran pointage pour adapter l'UI.
     *
     * @return array<string, mixed>
     */
    public static function windowInfo(?Carbon $d = null): array
    {
        $d = $d ?? Carbon::now();
        $inactif = self::isJourQrInactif($d);
        $optionnel = self::isJourPointageOptionnel($d);

        return [
            'date' => $d->toDateString(),
            'day_of_week' => (int) $d->dayOfWeek,
            'jour_libelle' => self::libelleJour((int) $d->dayOfWeek),
            'qr_actif' => ! $inactif,
            'pointage_obligatoire' => ! $inactif && ! $optionnel,
            'qr_inactif' => $inactif,
            'pointage_optionnel' => $optionnel,
            'message' => $inactif
                ? self::messageQrInactif($d)
                : ($optionnel
                    ? 'Le pointage du '.self::libelleJour((int) $d->dayOfWeek).' n\'est pas obligatoire — seuls les staffs présents pointent.'
                    : null),
            'jours_qr_inactifs' => self::joursQrInactifs(),
            'jours_qr_optionnels' => self::joursQrOptionnels(),
        ];
    }

    private static function libelleJour(int $dow): string
    {
        return match ($dow) {
            0 => 'dimanche',
            1 => 'lundi',
            2 => 'mardi',
            3 => 'mercredi',
            4 => 'jeudi',
            5 => 'vendredi',
            6 => 'samedi',
            default => 'jour',
        };
    }

    /**
     * @param  list<int>  $default
     * @return list<int>
     */
    private static function parseDows(string $csv, array $default): array
    {
        if (trim($csv) === '') {
            return $default;
        }
        $out = [];
        foreach (explode(',', $csv) as $part) {
            $part = trim($part);
            if ($part === '' || ! is_numeric($part)) {
                continue;
            }
            $n = (int) $part;
            if ($n < 0 || $n > 6) {
                continue;
            }
            $out[] = $n;
        }

        return $out === [] ? $default : array_values(array_unique($out));
    }
}

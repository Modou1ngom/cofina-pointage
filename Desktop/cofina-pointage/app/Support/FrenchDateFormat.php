<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Formats date/heure « à la française » pour l'UI et les exports.
 *
 *  - heure   : 16h30      (24h, séparateur « h »)
 *  - heureSec: 16h30:05
 *  - date    : 25/05/2026
 *  - dateLong: lundi 25 mai 2026
 *  - dateTime: 25/05/2026 16h30
 */
final class FrenchDateFormat
{
    public static function heure(?CarbonInterface $d): string
    {
        return $d === null ? '—' : $d->format('H\hi');
    }

    public static function heureSec(?CarbonInterface $d): string
    {
        return $d === null ? '—' : $d->format('H\hi:s');
    }

    /**
     * Accepte « 8:00 », « 08:00 », « 8 », « 8h », « 8h0 » → « 08h00 ».
     */
    public static function heureFromString(?string $hhmm): string
    {
        if ($hhmm === null || $hhmm === '') {
            return '—';
        }
        $clean = str_replace(['h', 'H'], ':', $hhmm);
        $parts = explode(':', $clean);
        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);

        return sprintf('%02dh%02d', $h, $m);
    }

    public static function date(?CarbonInterface $d): string
    {
        return $d === null ? '—' : $d->format('d/m/Y');
    }

    public static function dateLong(?CarbonInterface $d): string
    {
        if ($d === null) {
            return '—';
        }

        return $d->locale('fr')->isoFormat('dddd D MMMM YYYY');
    }

    public static function dateTime(?CarbonInterface $d): string
    {
        return $d === null ? '—' : $d->format('d/m/Y H\hi');
    }

    public static function dateTimeLong(?CarbonInterface $d): string
    {
        if ($d === null) {
            return '—';
        }

        return $d->locale('fr')->isoFormat('dddd D MMMM YYYY [à] H[h]mm');
    }
}

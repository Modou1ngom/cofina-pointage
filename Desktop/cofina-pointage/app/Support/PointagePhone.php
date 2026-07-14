<?php

namespace App\Support;

/**
 * Normalisation téléphone pour SMS (E.164), indicatif par défaut configurable (Sénégal 221).
 */
final class PointagePhone
{
    public static function toE164(?string $telephone, ?string $defaultCallingCode = null): ?string
    {
        $cc = preg_replace('/\D/', '', (string) ($defaultCallingCode ?? config('pointage.otp_sms_default_calling_code', '221'))) ?? '';
        if ($cc === '') {
            $cc = '221';
        }

        $raw = trim((string) $telephone);
        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }

        while (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, $cc)) {
            return self::validateLength('+'.$digits);
        }

        // Trunk national sénégalais : 0 + 9 chiffres (ex. 0777377821)
        if ($cc === '221' && strlen($digits) === 10 && str_starts_with($digits, '0') && ($digits[1] ?? '') === '7') {
            $digits = substr($digits, 1);

            return self::validateLength('+'.$cc.$digits);
        }

        // Mobile local 9 chiffres (ex. 777377821)
        if ($cc === '221' && strlen($digits) === 9 && str_starts_with($digits, '7')) {
            return self::validateLength('+'.$cc.$digits);
        }

        // Autre pays / numéro déjà complet sans indicatif explicite : préfixer l’indicatif configuré
        if (strlen($digits) >= 8 && strlen($digits) <= 12) {
            return self::validateLength('+'.$cc.$digits);
        }

        return null;
    }

    private static function validateLength(string $e164): ?string
    {
        $digits = preg_replace('/\D/', '', $e164) ?? '';

        // E.164 : indicatif + 8–15 chiffres au total (ITU-T E.164)
        $len = strlen($digits);
        if ($len < 8 || $len > 15) {
            return null;
        }

        return '+'.$digits;
    }
}

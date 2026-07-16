<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NagerPublicHolidaysService
{
    private const BASE_URL = 'https://date.nager.at/api/v3/PublicHolidays';

    /**
     * @return array{ok: bool, items: list<array{date: string, localName: string, name: string, countryCode: string, types?: array<int, string>}>, error: string|null}
     */
    public function fetchSafe(int $year, string $countryCode): array
    {
        $countryCode = strtoupper(trim($countryCode));
        if ($countryCode === '' || strlen($countryCode) > 3) {
            return ['ok' => false, 'items' => [], 'error' => 'Code pays invalide.'];
        }

        $url = self::BASE_URL.'/'.$year.'/'.$countryCode;

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning('Nager.date indisponible', ['url' => $url, 'message' => $e->getMessage()]);

            return ['ok' => false, 'items' => [], 'error' => 'Réseau indisponible ou timeout. Réessayez ou saisissez les fériés manuellement.'];
        }

        if ($response->status() === 404) {
            return ['ok' => false, 'items' => [], 'error' => 'Pays non pris en charge par l’API Nager.date pour cette année (404).'];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'items' => [], 'error' => 'Erreur API ('.$response->status().').'];
        }

        /** @var mixed $decoded */
        $decoded = $response->json();
        if (! is_array($decoded)) {
            return ['ok' => false, 'items' => [], 'error' => 'Réponse API inattendue.'];
        }

        $items = [];
        foreach ($decoded as $row) {
            if (! is_array($row)) {
                continue;
            }
            $date = isset($row['date']) && is_string($row['date']) ? $row['date'] : null;
            if ($date === null || strlen($date) < 10) {
                continue;
            }
            $local = isset($row['localName']) && is_string($row['localName']) ? $row['localName'] : '';
            $name = isset($row['name']) && is_string($row['name']) ? $row['name'] : '';
            $cc = isset($row['countryCode']) && is_string($row['countryCode']) ? strtoupper($row['countryCode']) : $countryCode;
            $types = isset($row['types']) && is_array($row['types']) ? array_values(array_filter($row['types'], 'is_string')) : [];
            $items[] = [
                'date' => substr($date, 0, 10),
                'localName' => $local !== '' ? $local : $name,
                'name' => $name !== '' ? $name : $local,
                'countryCode' => $cc,
                'types' => $types,
            ];
        }

        return ['ok' => true, 'items' => $items, 'error' => null];
    }
}

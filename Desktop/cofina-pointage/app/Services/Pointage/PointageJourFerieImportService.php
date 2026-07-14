<?php

namespace App\Services\Pointage;

use App\Models\PointageJourFerie;
use Illuminate\Support\Facades\DB;

class PointageJourFerieImportService
{
    /**
     * @param  list<array{date: string, libelle: string}>  $holidays
     * @return array{created: int, skipped: int}
     */
    public function importOfficialHolidays(int $year, string $countryCode, array $holidays): array
    {
        $countryCode = strtoupper(trim($countryCode));
        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($year, $countryCode, $holidays, &$created, &$skipped): void {
            foreach ($holidays as $row) {
                $date = $row['date'] ?? null;
                $libelle = $row['libelle'] ?? null;
                if (! is_string($date) || strlen($date) < 10 || ! is_string($libelle) || $libelle === '') {
                    $skipped++;

                    continue;
                }

                $date = substr($date, 0, 10);
                $y = (int) substr($date, 0, 4);
                if ($y !== $year) {
                    $skipped++;

                    continue;
                }

                $exists = PointageJourFerie::query()
                    ->where('source', 'official')
                    ->where('country_code', $countryCode)
                    ->whereDate('date_unique', $date)
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                PointageJourFerie::query()->create([
                    'libelle' => mb_substr($libelle, 0, 191),
                    'date_unique' => $date,
                    'date_fin' => null,
                    'recurrence_annuelle' => false,
                    'pays_region' => null,
                    'country_code' => $countryCode,
                    'departement_id' => null,
                    'type' => 'national',
                    'travaille_avec_majoration' => false,
                    'taux_majoration_pct' => 0,
                    'source' => 'official',
                    'annee' => $year,
                    'notes' => 'Import Nager.date',
                ]);
                $created++;
            }
        });

        return ['created' => $created, 'skipped' => $skipped];
    }
}

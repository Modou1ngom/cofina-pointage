<?php

namespace App\Console\Commands;

use App\Models\PointageFerieImportPref;
use App\Models\PointageJourFerie;
use App\Services\NagerPublicHolidaysService;
use App\Services\Pointage\PointageJourFerieAutoPointageService;
use App\Services\Pointage\PointageJourFerieImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PointageFeriesNagerImportAnnuel extends Command
{
    protected $signature = 'pointage:feries-import-nager {--year= : Année cible (défaut : année en cours)}';

    protected $description = 'Importe les jours fériés officiels (Nager.date) pour les pays avec l’option « import automatique chaque année ».';

    public function handle(
        NagerPublicHolidaysService $nager,
        PointageJourFerieImportService $importer,
        PointageJourFerieAutoPointageService $autoPointage,
    ): int {
        if (! Schema::hasTable('pointage_ferie_import_prefs')) {
            $this->error('Table pointage_ferie_import_prefs absente. Exécutez : php artisan migrate');

            return self::FAILURE;
        }

        $year = (int) ($this->option('year') ?: now()->year);

        $prefs = PointageFerieImportPref::query()->where('auto_importer_annuel', true)->get();
        if ($prefs->isEmpty()) {
            $this->info('Aucun pays avec import automatique annuel activé.');

            return self::SUCCESS;
        }

        foreach ($prefs as $pref) {
            $res = $nager->fetchSafe($year, $pref->country_code);
            if (! $res['ok']) {
                $this->warn("{$pref->country_code} : {$res['error']}");

                continue;
            }
            $holidays = [];
            foreach ($res['items'] as $it) {
                $holidays[] = [
                    'date' => $it['date'],
                    'libelle' => $it['localName'] !== '' ? $it['localName'] : $it['name'],
                ];
            }
            $stats = $importer->importOfficialHolidays($year, $pref->country_code, $holidays);

            $autoCreated = 0;
            foreach ($holidays as $row) {
                $date = $row['date'] ?? null;
                if (! is_string($date)) {
                    continue;
                }
                $f = PointageJourFerie::query()
                    ->where('source', 'official')
                    ->where('country_code', strtoupper($pref->country_code))
                    ->whereDate('date_unique', substr($date, 0, 10))
                    ->first();
                if ($f === null || $f->travaille_avec_majoration) {
                    continue;
                }
                $res = $autoPointage->generateForFerie($f, includePastDates: false);
                $autoCreated += $res['created_pointages'];
            }

            $this->line("{$pref->country_code} : {$stats['created']} créés, {$stats['skipped']} ignorés, {$autoCreated} pointage(s) auto staff.");
        }

        return self::SUCCESS;
    }
}

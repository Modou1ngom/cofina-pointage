<?php

namespace App\Console\Commands;

use App\Models\PointageJourFerie;
use App\Services\Pointage\PointageJourFerieAutoPointageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PointageFeriesRecurrenceAnnuelle extends Command
{
    protected $signature = 'pointage:feries-prolonger-recurrence {--year= : Année cible (défaut : année en cours)}';

    protected $description = 'Recrée les jours fériés à récurrence annuelle pour l’année cible (même jour/mois).';

    public function handle(PointageJourFerieAutoPointageService $autoPointage): int
    {
        $year = (int) ($this->option('year') ?: now()->year);
        $templates = PointageJourFerie::query()->where('recurrence_annuelle', true)->get();
        $created = 0;
        $skipped = 0;
        $createdFeries = [];

        DB::transaction(function () use ($templates, $year, &$created, &$skipped, &$createdFeries): void {
            foreach ($templates as $t) {
                $month = (int) $t->date_unique->month;
                $day = (int) $t->date_unique->day;
                if (! checkdate($month, $day, $year)) {
                    $skipped++;

                    continue;
                }
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

                $exists = PointageJourFerie::query()
                    ->where('libelle', $t->libelle)
                    ->whereDate('date_unique', $date)
                    ->when($t->country_code, fn ($q) => $q->where('country_code', $t->country_code))
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                $createdFeries[] = PointageJourFerie::query()->create([
                    'libelle' => $t->libelle,
                    'date_unique' => $date,
                    'date_fin' => $t->date_fin ? sprintf('%04d-%02d-%02d', $year, (int) $t->date_fin->month, (int) $t->date_fin->day) : null,
                    'recurrence_annuelle' => true,
                    'pays_region' => $t->pays_region,
                    'departement_id' => $t->departement_id,
                    'country_code' => $t->country_code,
                    'type' => $t->type,
                    'travaille_avec_majoration' => $t->travaille_avec_majoration,
                    'taux_majoration_pct' => $t->taux_majoration_pct,
                    'source' => $t->source === 'official' ? 'official' : 'manual',
                    'annee' => $year,
                    'notes' => $t->notes,
                ]);
                $created++;
            }
        });

        $autoCreated = 0;
        foreach ($createdFeries as $f) {
            if ($f->travaille_avec_majoration) {
                continue;
            }
            $res = $autoPointage->generateForFerie($f, includePastDates: false);
            $autoCreated += $res['created_pointages'];
        }

        $this->info("Année {$year} : {$created} créés, {$skipped} ignorés, {$autoCreated} pointage(s) auto staff générés.");

        return self::SUCCESS;
    }
}

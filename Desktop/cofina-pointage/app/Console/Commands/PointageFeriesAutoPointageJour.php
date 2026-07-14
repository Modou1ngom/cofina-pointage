<?php

namespace App\Console\Commands;

use App\Services\Pointage\PointageJourFerieAutoPointageService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PointageFeriesAutoPointageJour extends Command
{
    protected $signature = 'pointage:feries-auto-pointage
        {--date= : Date a traiter au format Y-m-d (defaut : aujourd hui)}';

    protected $description = 'Si la date est un jour férié chômé, crée automatiquement les pointages (arrivée + départ) pour tous les staffs concernés.';

    public function handle(PointageJourFerieAutoPointageService $service): int
    {
        $opt = $this->option('date');
        try {
            $date = is_string($opt) && $opt !== '' ? Carbon::parse($opt) : Carbon::today();
        } catch (\Throwable) {
            $this->error('Date invalide. Format attendu : Y-m-d.');

            return self::FAILURE;
        }

        $res = $service->generateForDate($date);

        if (! isset($res['ferie'])) {
            $this->info("{$date->toDateString()} : aucun jour férié déclaré en base, rien à faire.");

            return self::SUCCESS;
        }

        if (($res['reason'] ?? null) === 'ferie_travaille_majoration') {
            $this->warn(sprintf(
                '%s — férié « %s » détecté MAIS marqué "travaillé avec majoration" : pas d\'auto-pointage. Décochez la case "Travaillé avec majoration" dans Pointage RH → Jours fériés pour le rendre chômé.',
                $date->toDateString(),
                $res['ferie']->libelle
            ));

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s — férié « %s » : %d pointage(s) créés, %d staff(s) ignorés (déjà pointés ou sans agence).',
            $date->toDateString(),
            $res['ferie']->libelle,
            $res['created_pointages'],
            $res['skipped_users']
        ));

        return self::SUCCESS;
    }
}

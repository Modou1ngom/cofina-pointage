<?php

namespace App\Services\Pointage;

use App\Models\PointageHoraireProfile;
use App\Models\PointageJourFerie;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Règles calendaires pour présence / paie : jours ouvrables, week-ends, fériés, majorations.
 */
class PointageHorairesCalendrierService
{
    /**
     * Tous les fériés applicables (priorité manuel sur officiel en cas de chevauchement).
     *
     * @return Collection<int, PointageJourFerie>
     */
    public function feriesChargees(): Collection
    {
        return $this->queryFeriesFiltered(null, null);
    }

    /**
     * @return Collection<int, PointageJourFerie>
     */
    public function queryFeriesFiltered(?string $countryCode, ?int $departementId): Collection
    {
        $q = PointageJourFerie::query()
            ->orderByRaw("CASE WHEN source = 'manual' THEN 0 ELSE 1 END")
            ->orderByDesc('id');

        if ($countryCode !== null && $countryCode !== '' && strtolower($countryCode) !== 'all') {
            $cc = strtoupper($countryCode);
            $q->where(function ($w) use ($cc) {
                $w->whereNull('country_code')->orWhere('country_code', $cc);
            });
        }

        if ($departementId !== null && $departementId > 0) {
            $q->where(function ($w) use ($departementId) {
                $w->whereNull('departement_id')->orWhere('departement_id', $departementId);
            });
        }

        return $q->get();
    }

    public function feriePourDate(Carbon $d, ?Collection $feries = null): ?PointageJourFerie
    {
        $feries ??= $this->feriesChargees();

        foreach ($feries as $f) {
            if ($this->dateMatchesFerie($d, $f)) {
                return $f;
            }
        }

        return null;
    }

    public function dateMatchesFerie(Carbon $d, PointageJourFerie $f): bool
    {
        if ($f->recurrence_annuelle) {
            return (int) $f->date_unique->month === (int) $d->month
                && (int) $f->date_unique->day === (int) $d->day;
        }

        $start = $f->date_unique->copy()->startOfDay();
        $end = ($f->date_fin ?? $f->date_unique)->copy()->endOfDay();
        $cur = $d->copy()->startOfDay();

        return $cur->gte($start) && $cur->lte($end);
    }

    public function isWeekend(Carbon $d, PointageHoraireProfile $profile): bool
    {
        $jours = $profile->weekend_jours ?? [Carbon::SATURDAY, Carbon::SUNDAY];

        return in_array((int) $d->dayOfWeek, array_map('intval', $jours), true);
    }

    /**
     * Jour attendu dans la base « ouvrés » (exclut week-end profil et fériés chômés).
     */
    public function jourCompteDansBasePresence(Carbon $d, PointageHoraireProfile $profile, ?Collection $feries = null): bool
    {
        $feries ??= $this->feriesChargees();
        if ($this->isWeekend($d, $profile)) {
            return false;
        }
        $f = $this->feriePourDate($d, $feries);
        if ($f !== null && ! $f->travaille_avec_majoration) {
            return false;
        }

        return true;
    }

    /**
     * Absence sur férié chômé : ne doit pas être comptabilisée comme absence.
     */
    public function absenceSurJourFerieChomeeNeComptePas(?PointageJourFerie $f): bool
    {
        return $f !== null && ! $f->travaille_avec_majoration;
    }

    /**
     * Taux de majoration (%) applicable aux heures pointées sur un jour férié (chômé ou travaillé majoré).
     */
    public function majorationPourHeuresPointees(?PointageJourFerie $f): float
    {
        if ($f === null) {
            return 0.0;
        }
        $p = (float) $f->taux_majoration_pct;
        if ($f->travaille_avec_majoration) {
            return $p;
        }
        if ($p > 0) {
            return $p;
        }

        return (float) config('pointage.ferie_presence_majoration_defaut_pct', 0);
    }

    /**
     * @deprecated Utiliser {@see majorationPourHeuresPointees} — conservé pour compatibilité.
     */
    public function tauxMajorationFerie(?PointageJourFerie $f): float
    {
        return $this->majorationPourHeuresPointees($f);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function grilleMensuelle(int $year, int $month, PointageHoraireProfile $profile, ?Collection $feries = null): array
    {
        $feries ??= $this->feriesChargees();
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $out = [];

        foreach (CarbonPeriod::create($start, $end) as $day) {
            /** @var Carbon $day */
            $f = $this->feriePourDate($day, $feries);
            $weekend = $this->isWeekend($day, $profile);
            $type = $f !== null ? 'ferie' : ($weekend ? 'weekend' : 'ouvrable');
            $partiel = $this->weekendPartielMatin($day, $profile);
            $ferieSubtype = $f ? ($f->travaille_avec_majoration ? 'majore' : 'chome') : null;

            $out[] = [
                'date' => $day->toDateString(),
                'dow' => (int) $day->dayOfWeek,
                'type' => $type,
                'ferie_subtype' => $ferieSubtype,
                'ferie_source' => $f?->source,
                'ferie_id' => $f?->id,
                'libelle' => $f?->libelle,
                'majoration_pct' => $this->majorationAfficheeCalendrier($f),
                'partiel' => $partiel,
            ];
        }

        return $out;
    }

    /**
     * @return array<int, list<array<string, mixed>>>
     */
    public function grilleAnnuelle(int $year, PointageHoraireProfile $profile, ?Collection $feries = null): array
    {
        $feries ??= $this->feriesChargees();
        $out = [];
        for ($m = 1; $m <= 12; $m++) {
            $out[$m] = $this->grilleMensuelle($year, $m, $profile, $feries);
        }

        return $out;
    }

    private function majorationAfficheeCalendrier(?PointageJourFerie $f): ?float
    {
        if ($f === null) {
            return null;
        }
        $p = (float) $f->taux_majoration_pct;
        if ($f->travaille_avec_majoration) {
            return $p > 0 ? $p : null;
        }
        if ($p > 0) {
            return $p;
        }
        $d = (float) config('pointage.ferie_presence_majoration_defaut_pct', 0);

        return $d > 0 ? $d : null;
    }

    public function weekendPartielMatin(Carbon $d, PointageHoraireProfile $profile): bool
    {
        if ((int) $d->dayOfWeek === Carbon::SATURDAY && $profile->weekend_samedi_matin_ouvrable) {
            return true;
        }
        if ((int) $d->dayOfWeek === Carbon::SUNDAY && $profile->weekend_dimanche_matin_ouvrable) {
            return true;
        }

        return false;
    }

    /**
     * Heures sup week-end travaillé : taux configuré sur le profil horaire.
     */
    public function tauxMajorationWeekendTravaille(PointageHoraireProfile $profile): float
    {
        return (float) ($profile->weekend_travail_majoration_pct ?? 0);
    }
}

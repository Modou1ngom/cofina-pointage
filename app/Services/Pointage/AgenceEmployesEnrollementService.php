<?php

namespace App\Services\Pointage;

use App\Models\Agence;
use App\Models\PointageAffectation;
use Illuminate\Support\Facades\DB;

class AgenceEmployesEnrollementService
{
    /**
     * Rattache l'agence aux affectations pointage liées (profil sur ce site ou déjà sur l'agence),
     * puis synchronise le pivot agence_user.
     *
     * @return int Nombre d'utilisateurs distincts synchronisés
     */
    public function syncEmployesEnrolesPourAgence(Agence $agence): int
    {
        $nomNormalise = $this->normaliserNom($agence->nom);
        if ($nomNormalise === '') {
            return 0;
        }

        $syncedUserIds = [];

        $affectations = PointageAffectation::query()
            ->where('statut_activation', true)
            ->where(function ($q) use ($agence, $nomNormalise) {
                $q->whereHas('agences', fn ($aq) => $aq->where('agences.id', $agence->id))
                    ->orWhereHas('profil', fn ($pq) => $pq->whereRaw('LOWER(TRIM(site)) = ?', [$nomNormalise]));
            })
            ->with(['profil', 'agences'])
            ->get();

        foreach ($affectations as $affectation) {
            $affectation->syncUserLinkFromProfilEmail();

            if (! $affectation->agences->contains('id', $agence->id)) {
                $isDefault = $affectation->agences->isEmpty();
                if ($isDefault) {
                    $this->clearDefaultOnAffectation($affectation);
                }

                $affectation->agences()->attach($agence->id, [
                    'is_default' => $isDefault,
                    'statut_agence' => 'actif',
                    'niveau_acces' => 'pointage_complet',
                ]);
                $affectation->unsetRelation('agences');
                $affectation->load('agences');
            }

            $affectation->syncAgencesToUserPivot();

            if ($affectation->user_id) {
                $syncedUserIds[$affectation->user_id] = true;
            }
        }

        return count($syncedUserIds);
    }

    public function countEmployesEnrolesPourAgence(Agence $agence): int
    {
        $counts = $this->countEmployesEnrolesPourAgenceIds([$agence->id]);

        return $counts[$agence->id] ?? 0;
    }

    /**
     * @param  int[]  $agenceIds
     * @return array<int, int>
     */
    public function countEmployesEnrolesPourAgenceIds(array $agenceIds): array
    {
        $agenceIds = array_values(array_unique(array_filter(array_map('intval', $agenceIds))));
        if ($agenceIds === []) {
            return [];
        }

        $userSets = array_fill_keys($agenceIds, []);

        $pivotRows = DB::table('agence_user')
            ->whereIn('agence_id', $agenceIds)
            ->select('agence_id', 'user_id')
            ->get();

        foreach ($pivotRows as $row) {
            $userSets[(int) $row->agence_id][(int) $row->user_id] = true;
        }

        $affectationRows = DB::table('pointage_affectation_agences as paa')
            ->join('pointage_affectations as pa', 'pa.id', '=', 'paa.pointage_affectation_id')
            ->whereIn('paa.agence_id', $agenceIds)
            ->where('pa.statut_activation', true)
            ->whereNotNull('pa.user_id')
            ->select('paa.agence_id', 'pa.user_id')
            ->get();

        foreach ($affectationRows as $row) {
            $userSets[(int) $row->agence_id][(int) $row->user_id] = true;
        }

        $counts = [];
        foreach ($userSets as $agenceId => $users) {
            $counts[$agenceId] = count($users);
        }

        return $counts;
    }

    /**
     * Employés enrôlés au pointage dont le profil est rattaché à ce site (avant création de l'agence).
     */
    public function countEmployesEnrolesPourSiteNom(string $siteNom): int
    {
        $nomNormalise = $this->normaliserNom($siteNom);
        if ($nomNormalise === '') {
            return 0;
        }

        $userIds = [];

        $affectations = PointageAffectation::query()
            ->where('statut_activation', true)
            ->whereHas('profil', fn ($q) => $q->whereRaw('LOWER(TRIM(site)) = ?', [$nomNormalise]))
            ->get(['id', 'user_id', 'profil_id']);

        foreach ($affectations as $affectation) {
            $affectation->syncUserLinkFromProfilEmail();
            if ($affectation->user_id) {
                $userIds[$affectation->user_id] = true;
            }
        }

        return count($userIds);
    }

    private function normaliserNom(?string $nom): string
    {
        return mb_strtolower(trim((string) $nom));
    }

    private function clearDefaultOnAffectation(PointageAffectation $affectation): void
    {
        DB::table('pointage_affectation_agences')
            ->where('pointage_affectation_id', $affectation->id)
            ->update(['is_default' => false]);
    }
}

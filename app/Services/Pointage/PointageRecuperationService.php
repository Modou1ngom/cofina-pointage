<?php

namespace App\Services\Pointage;

use App\Models\Agence;
use App\Models\Pointage;
use App\Models\Profil;
use App\Support\FrenchDateFormat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Consultation / récupération des pointages bruts (lignes horodatées) pour le RH.
 */
final class PointageRecuperationService
{
    /**
     * @return array{
     *     date_debut: string,
     *     date_fin: string,
     *     agence_id: int|null,
     *     q: string,
     *     type: string,
     *     statut: string,
     * }
     */
    public function parseFilters(array $input): array
    {
        $today = Carbon::today();
        $debutRaw = $input['date_debut'] ?? $today->copy()->startOfMonth()->toDateString();
        $finRaw = $input['date_fin'] ?? $today->toDateString();

        try {
            $debut = Carbon::parse(is_string($debutRaw) ? $debutRaw : $today->toDateString())->startOfDay();
        } catch (\Throwable) {
            $debut = $today->copy()->startOfMonth()->startOfDay();
        }

        try {
            $fin = Carbon::parse(is_string($finRaw) ? $finRaw : $today->toDateString())->endOfDay();
        } catch (\Throwable) {
            $fin = $today->copy()->endOfDay();
        }

        if ($fin->lt($debut)) {
            [$debut, $fin] = [$fin->copy()->startOfDay(), $debut->copy()->endOfDay()];
        }

        $agenceId = isset($input['agence_id']) && $input['agence_id'] !== '' && $input['agence_id'] !== 'tous'
            ? (int) $input['agence_id']
            : null;

        $q = is_string($input['q'] ?? null) ? trim($input['q']) : '';

        $type = is_string($input['type'] ?? null) ? $input['type'] : 'tous';
        if (! in_array($type, ['tous', 'arrivee', 'depart'], true)) {
            $type = 'tous';
        }

        $statut = is_string($input['statut'] ?? null) ? $input['statut'] : 'tous';
        if (! in_array($statut, ['tous', 'normal', 'retard', 'ferie_auto', 'absent'], true)) {
            $statut = 'tous';
        }

        return [
            'date_debut' => $debut->toDateString(),
            'date_fin' => $fin->toDateString(),
            'agence_id' => $agenceId > 0 ? $agenceId : null,
            'q' => $q,
            'type' => $type,
            'statut' => $statut,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function baseQuery(array $filters): Builder
    {
        $debut = Carbon::parse($filters['date_debut'])->startOfDay();
        $fin = Carbon::parse($filters['date_fin'])->endOfDay();

        $q = Pointage::query()
            ->with(['user.profil', 'agence'])
            ->whereBetween('clocked_at', [$debut, $fin]);

        if ($filters['agence_id'] !== null) {
            $q->where('agence_id', $filters['agence_id']);
        }

        if ($filters['type'] !== 'tous') {
            $q->where('type', $filters['type']);
        }

        if ($filters['statut'] !== 'tous') {
            if ($filters['statut'] === 'absent') {
                $q->whereRaw('0 = 1');
            } else {
                $q->where('statut', $filters['statut']);
            }
        }

        if ($filters['q'] !== '') {
            $term = '%'.mb_strtolower($filters['q']).'%';
            $q->whereHas('user', function (Builder $w) use ($term): void {
                $w->where(function (Builder $inner) use ($term): void {
                    $inner->whereRaw('LOWER(email) LIKE ?', [$term])
                        ->orWhereHas('profil', function (Builder $p) use ($term): void {
                            $p->whereRaw('LOWER(nom) LIKE ?', [$term])
                                ->orWhereRaw('LOWER(prenom) LIKE ?', [$term])
                                ->orWhereRaw('LOWER(matricule) LIKE ?', [$term])
                                ->orWhereRaw("LOWER(CONCAT(prenom, ' ', nom)) LIKE ?", [$term]);
                        });
                });
            });
        }

        return $q->orderByDesc('clocked_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int|float>
     */
    public function kpis(array $filters): array
    {
        $base = $this->baseQuery($filters);
        $clone = fn () => (clone $base);

        $total = $clone()->count();
        $employes = (int) $clone()->distinct('user_id')->count('user_id');
        $arrivees = $clone()->where('type', 'arrivee')->count();
        $departs = $clone()->where('type', 'depart')->count();
        $retards = $clone()->where('statut', 'retard')->count();
        $ferieAuto = $clone()->where('statut', 'ferie_auto')->count();

        return [
            'total_lignes' => $total,
            'employes' => $employes,
            'arrivees' => $arrivees,
            'departs' => $departs,
            'retards' => $retards,
            'ferie_auto' => $ferieAuto,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mapRow(Pointage $p): array
    {
        $user = $p->user;
        $profil = $user?->profil;
        $meta = $p->meta ?? [];

        $employe = $profil
            ? trim(($profil->prenom ?? '').' '.($profil->nom ?? ''))
            : ($user?->full_name ?: $user?->name ?: '—');

        $statutLabel = match ($p->statut) {
            'retard' => 'Retard',
            'ferie_auto' => 'Férié (auto)',
            'normal' => 'Normal',
            default => ucfirst((string) $p->statut),
        };

        return [
            'id' => $p->id,
            'user_id' => $p->user_id,
            'date' => FrenchDateFormat::date($p->clocked_at),
            'date_iso' => $p->clocked_at->toDateString(),
            'employe' => $employe !== '' ? $employe : '—',
            'email' => $user?->email ?? '—',
            'matricule' => $profil?->matricule ?: '—',
            'service' => $profil?->departement ?: '—',
            'agence' => $p->agence?->nom ?? ($profil?->site ?: '—'),
            'type' => $p->type,
            'type_label' => $p->type === 'arrivee' ? 'Arrivée' : 'Départ',
            'heure_effective' => $p->heureAffichee(),
            'heure_reelle' => $p->heureReelleAffichee(),
            'horodatage' => FrenchDateFormat::dateTime($p->clocked_at),
            'gps_ok' => $p->latitude !== null && $p->longitude !== null,
            'biometric_ok' => (bool) $p->biometric_ok,
            'qr_verified' => (bool) $p->qr_verified,
            'statut' => $p->statut,
            'statut_label' => $statutLabel,
            'auto_ferie' => ($meta['auto_ferie'] ?? false) === true,
            'ferie_libelle' => is_string($meta['ferie_libelle'] ?? null) ? $meta['ferie_libelle'] : null,
        ];
    }

    /**
     * @return list<string>
     */
    public function sitesOptions(): array
    {
        return Agence::query()->where('actif', true)->orderBy('nom')->pluck('nom')
            ->merge(
                Profil::query()
                    ->where('statut', 'actif')
                    ->whereNotNull('site')
                    ->where('site', '!=', '')
                    ->distinct()
                    ->orderBy('site')
                    ->pluck('site')
            )
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, nom: string}>
     */
    public function agencesOptions(): array
    {
        return Agence::query()
            ->where('actif', true)
            ->orderBy('nom')
            ->get(['id', 'nom'])
            ->map(fn (Agence $a) => ['id' => $a->id, 'nom' => $a->nom])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function periodeLabel(array $filters): string
    {
        $debut = Carbon::parse($filters['date_debut']);
        $fin = Carbon::parse($filters['date_fin']);

        if ($debut->isSameDay($fin)) {
            return FrenchDateFormat::dateLong($debut);
        }

        return FrenchDateFormat::date($debut).' → '.FrenchDateFormat::date($fin);
    }
}

<?php

namespace App\Services\Pointage;

use App\Models\Pointage;
use App\Models\PointageHoraireProfile;
use App\Models\User;
use App\Support\MobileApiAccountResource;
use App\Support\MobileApiUserResource;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class PointageEmployeProfilService
{
    public function __construct(
        private readonly PointageHorairesCalendrierService $calendrier,
    ) {}

    /**
     * Données « Mon profil » pour l’API mobile (aligné sur la page web Pointage/Profil).
     *
     * @return array<string, mixed>
     */
    public function apiPayload(User $user): array
    {
        $user->profilCollaborateurAssocie();
        $p = $user->profil;
        $now = Carbon::now();
        $horaireStandard = $this->horaireStandardLabel();
        $statsMois = $this->statsMois($user->id, $now);
        $telephoneAffiche = $this->maskTelephone($p?->telephone);
        $service = $p?->departement ?: $p?->fonction;

        return array_merge(
            MobileApiAccountResource::toArray($user),
            MobileApiUserResource::toArray($user),
            [
                'service' => $service,
                'telephone_affiche' => $telephoneAffiche,
                'telephoneAffiche' => $telephoneAffiche,
                'horaire_standard' => $horaireStandard,
                'horaireStandard' => $horaireStandard,
                'stats_mois' => $statsMois,
                'statsMois' => $statsMois,
            ],
        );
    }

    /**
     * @return array{profil: \App\Models\Profil|null, horaire_standard: string, stats_mois: array<string, mixed>, telephone_affiche: string|null}
     */
    public function forWeb(User $user): array
    {
        $user->profilCollaborateurAssocie();
        $now = Carbon::now();

        return [
            'profil' => $user->profil,
            'horaire_standard' => $this->horaireStandardLabel(),
            'stats_mois' => $this->statsMois($user->id, $now),
            'telephone_affiche' => $this->maskTelephone($user->profil?->telephone),
        ];
    }

    public function horaireStandardLabel(): string
    {
        $heureArr = (string) config('pointage.heure_arrivee', '08:00');
        $heureDep = (string) config('pointage.heure_depart', '17:00');
        try {
            $hA = Carbon::createFromFormat('H:i', strlen($heureArr) === 5 ? $heureArr : '08:00')->format('H\hi');
            $hD = Carbon::createFromFormat('H:i', strlen($heureDep) === 5 ? $heureDep : '17:00')->format('H\hi');
        } catch (\Throwable) {
            $hA = '08h00';
            $hD = '17h00';
        }

        return $hA.' — '.$hD;
    }

    /**
     * @return array<string, mixed>
     */
    public function statsMois(int $userId, Carbon $moisReference): array
    {
        return array_merge(
            [
                'periode_label' => mb_convert_case($moisReference->locale('fr')->translatedFormat('F Y'), MB_CASE_TITLE, 'UTF-8'),
                'periodeLabel' => mb_convert_case($moisReference->locale('fr')->translatedFormat('F Y'), MB_CASE_TITLE, 'UTF-8'),
                'semaines' => $this->semainesMois($userId, $moisReference),
            ],
            $this->presenceMois($userId, $moisReference),
        );
    }

    public function maskTelephone(?string $telephone): ?string
    {
        if ($telephone === null || trim($telephone) === '') {
            return null;
        }
        $t = trim($telephone);
        if (mb_strlen($t) < 10) {
            return $t;
        }

        return mb_substr($t, 0, 8).' XXX XX XX';
    }

    /**
     * @return list<array{label: string, heures: int, tone: string}>
     */
    private function semainesMois(int $userId, Carbon $moisReference): array
    {
        $start = $moisReference->copy()->startOfMonth()->startOfDay();
        $end = min($moisReference->copy()->endOfMonth()->endOfDay(), Carbon::now()->endOfDay());
        $lastDay = (int) $moisReference->copy()->endOfMonth()->format('d');

        $segments = [[1, 7], [8, 14], [15, 21], [22, $lastDay]];
        $bars = [];
        $i = 1;
        foreach ($segments as [$d1, $d2]) {
            if ($d1 > $lastDay) {
                $bars[] = ['label' => 'S'.$i, 'heures' => 0, 'tone' => 'neutral'];
                $i++;

                continue;
            }
            $d2c = min($d2, $lastDay);
            $from = $start->copy()->day($d1)->startOfDay();
            $to = $start->copy()->day($d2c)->endOfDay();
            if ($to->greaterThan($end)) {
                $to = $end->copy();
            }
            $mins = $this->minutesWorkedUserBetween($userId, $from, $to);
            $h = (int) floor($mins / 60);
            $tone = $h >= 40 ? 'good' : ($h >= 32 ? 'warn' : ($h > 0 ? 'bad' : 'neutral'));
            $bars[] = ['label' => 'S'.$i, 'heures' => $h, 'tone' => $tone];
            $i++;
        }

        return $bars;
    }

    /**
     * @return array{presence_pct: int, presencePct: int, presence_message: string, presenceMessage: string}
     */
    private function presenceMois(int $userId, Carbon $now): array
    {
        $monthStart = $now->copy()->startOfMonth()->startOfDay();
        $monthEnd = min($now->copy()->endOfDay(), $now->copy()->endOfMonth()->endOfDay());

        $joursOuvres = $this->countWeekdaysInRange($monthStart, $monthEnd);
        $joursPresents = (int) Pointage::query()
            ->where('user_id', $userId)
            ->whereBetween('clocked_at', [$monthStart, $monthEnd])
            ->where('type', 'arrivee')
            ->get()
            ->pluck('clocked_at')
            ->map(fn (Carbon $c) => $c->format('Y-m-d'))
            ->unique()
            ->count();

        $pct = $joursOuvres > 0 ? min(100, (int) round($joursPresents / $joursOuvres * 100)) : 0;

        $message = match (true) {
            $pct >= 95 => 'Excellent !',
            $pct >= 85 => 'Très bien.',
            $pct >= 70 => 'Correct — restez régulier.',
            default => 'À améliorer — contactez votre manager.',
        };

        return [
            'presence_pct' => $pct,
            'presencePct' => $pct,
            'presence_message' => $message,
            'presenceMessage' => $message,
        ];
    }

    private function minutesWorkedUserBetween(int $userId, Carbon $start, Carbon $end): int
    {
        $events = Pointage::query()
            ->where('user_id', $userId)
            ->whereBetween('clocked_at', [$start, $end])
            ->orderBy('clocked_at')
            ->get();

        $byDay = $events->groupBy(fn (Pointage $p) => $p->clocked_at->format('Y-m-d'));

        $total = 0;
        foreach ($byDay as $items) {
            /** @var Collection<int, Pointage> $items */
            $sorted = $items->sortBy('clocked_at')->values();
            $arrivee = $sorted->firstWhere('type', 'arrivee');
            $depart = $sorted->filter(fn (Pointage $p) => $p->type === 'depart')->sortByDesc(fn (Pointage $p) => $p->clocked_at)->first();
            if ($arrivee && $depart) {
                $total += (int) $arrivee->clocked_at->diffInMinutes($depart->clocked_at);
            }
        }

        return $total;
    }

    private function countWeekdaysInRange(Carbon $from, Carbon $to): int
    {
        $profile = $this->defaultHoraireProfile();
        $n = 0;
        $d = $from->copy()->startOfDay();
        while ($d->lte($to)) {
            if ($profile === null) {
                if (! $d->isWeekend()) {
                    $n++;
                }
            } elseif ($this->calendrier->jourCompteDansBasePresence($d, $profile)) {
                $n++;
            }
            $d->addDay();
        }

        return $n;
    }

    private function defaultHoraireProfile(): ?PointageHoraireProfile
    {
        return PointageHoraireProfile::query()
            ->where('scope_type', 'global')
            ->where('actif', true)
            ->orderBy('id')
            ->first()
            ?? PointageHoraireProfile::query()->orderBy('id')->first();
    }
}

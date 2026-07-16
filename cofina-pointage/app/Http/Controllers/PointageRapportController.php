<?php

namespace App\Http\Controllers;

use App\Models\Pointage;
use App\Models\PointageDeclaration;
use App\Models\Profil;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PointageRapportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && ($user->isRh() || $user->isAdmin() || $user->isSuperAdmin()), 403);

        return redirect()->route('pointage.rh.presence.recuperation-pointages');
    }

    public function exportMensuelRh(Request $request): StreamedResponse
    {
        $user = Auth::user();
        abort_unless($user && ($user->isRh() || $user->isAdmin() || $user->isSuperAdmin()), 403);

        $mois = $request->query('mois', now()->format('Y-m'));
        if (! is_string($mois) || ! preg_match('/^\d{4}-\d{2}$/', $mois)) {
            $mois = now()->format('Y-m');
        }
        try {
            $monthStart = Carbon::createFromFormat('Y-m-d', $mois.'-01')->startOfMonth();
        } catch (\Throwable) {
            $monthStart = now()->startOfMonth();
            $mois = $monthStart->format('Y-m');
        }
        $monthEnd = $monthStart->copy()->endOfMonth()->endOfDay();

        $userIds = $this->rapportRhActifUserIds();
        $rows = $this->rapportRhLignesEmployes($monthStart, $monthEnd, $userIds);

        $filename = 'rapport-rh-mensuel-'.$mois.'.csv';

        return $this->csvStream($filename, function () use ($rows): \Generator {
            yield ['Employé', 'Heures travaillées', 'Retards', 'Absences (validées)', 'Heures sup.', 'Pénalités (FCFA)'];

            foreach ($rows as $r) {
                yield [
                    $r['nom'],
                    $r['heures_travaillees'],
                    (string) $r['retards'],
                    (string) $r['absences'],
                    $r['heures_sup_label'],
                    $r['penalites_fcfa'] > 0 ? '-'.number_format($r['penalites_fcfa'], 0, ',', ' ') : '—',
                ];
            }
        });
    }

    public function exportQuotidien(Request $request): StreamedResponse
    {
        $user = Auth::user();
        abort_unless($user && ($user->isRh() || $user->isAdmin() || $user->isSuperAdmin()), 403);

        $date = Carbon::parse($request->get('date', today()->toDateString()))->toDateString();

        return $this->csvStream('pointage-quotidien-'.$date.'.csv', function () use ($date): \Generator {
            yield ['Horodatage', 'Utilisateur', 'Email', 'Site', 'Type', 'Statut', 'GPS ok'];

            $q = Pointage::query()
                ->with(['user:id,name,email', 'agence:id,nom'])
                ->whereDate('clocked_at', $date)
                ->orderBy('clocked_at');

            foreach ($q->cursor() as $p) {
                yield [
                    $p->clocked_at?->format('Y-m-d H:i:s'),
                    $p->user?->name,
                    $p->user?->email,
                    $p->agence?->nom,
                    $p->type,
                    $p->statut,
                    $p->latitude !== null ? 'oui' : 'non',
                ];
            }
        });
    }

    public function exportJournalierRh(Request $request): StreamedResponse
    {
        return $this->exportQuotidien($request);
    }

    public function exportSyntheseRh(Request $request): StreamedResponse
    {
        $user = Auth::user();
        abort_unless($user && ($user->isRh() || $user->isAdmin() || $user->isSuperAdmin()), 403);

        $du = Carbon::parse($request->get('du', now()->startOfMonth()->toDateString()))->startOfDay();
        $au = Carbon::parse($request->get('au', now()->toDateString()))->endOfDay();

        return $this->csvStream('pointage-synthese.csv', function () use ($du, $au): \Generator {
            yield ['Email', 'Nom', 'Nb pointages', 'Retards'];

            $aggregates = Pointage::query()
                ->selectRaw('user_id, COUNT(*) as total, SUM(CASE WHEN statut = ? THEN 1 ELSE 0 END) as retards', ['retard'])
                ->whereBetween('clocked_at', [$du, $au])
                ->groupBy('user_id')
                ->get();

            foreach ($aggregates as $row) {
                $u = User::query()->find($row->user_id);
                yield [
                    $u?->email,
                    $u?->name,
                    $row->total,
                    $row->retards,
                ];
            }
        });
    }

    /**
     * @param  callable(): \Generator  $rows
     */
    private function csvStream(string $filename, callable $rows): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            foreach ($rows() as $line) {
                fputcsv($out, $line, ';');
            }
            fclose($out);
        }, $filename, $headers);
    }

    /**
     * @return list<int>
     */
    private function rapportRhActifUserIds(): array
    {
        $emails = Profil::query()
            ->where('statut', 'actif')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereExists(function ($sub): void {
                $sub->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.email', 'profiles.email');
            })
            ->pluck('email')
            ->filter()
            ->unique()
            ->values();

        return User::query()
            ->whereIn('email', $emails)
            ->orderBy('name')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  list<int>  $userIds
     * @return array<string, int>
     */
    private function rapportRhKpis(Carbon $monthStart, Carbon $monthEnd, array $userIds): array
    {
        $effectifs = count($userIds);
        $jo = $this->countWeekdaysInRange($monthStart->copy()->startOfDay(), $monthEnd->copy()->endOfDay());

        $totalPresentDays = 0;
        foreach ($userIds as $uid) {
            $n = (int) Pointage::query()
                ->where('user_id', $uid)
                ->where('type', 'arrivee')
                ->whereBetween('clocked_at', [$monthStart, $monthEnd])
                ->get()
                ->pluck('clocked_at')
                ->map(fn (Carbon $c) => $c->format('Y-m-d'))
                ->unique()
                ->count();
            $totalPresentDays += $n;
        }

        $expected = max(1, $effectifs * max(1, $jo));
        $tauxPresence = min(100, (int) round($totalPresentDays / $expected * 100));

        $retardsMois = (int) Pointage::query()
            ->whereBetween('clocked_at', [$monthStart, $monthEnd])
            ->where('type', 'arrivee')
            ->where('statut', 'retard')
            ->whereIn('user_id', $userIds)
            ->count();

        $absJust = (int) PointageDeclaration::query()
            ->where('type', 'absence')
            ->where('statut', 'valide')
            ->whereBetween('date_concernee', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereIn('user_id', $userIds)
            ->count();

        $absNonJust = (int) PointageDeclaration::query()
            ->where('type', 'absence')
            ->whereNotIn('statut', ['valide'])
            ->whereBetween('date_concernee', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereIn('user_id', $userIds)
            ->count();

        return [
            'taux_presence_pct' => $tauxPresence,
            'retards_mois' => $retardsMois,
            'absences_justifiees' => $absJust,
            'absences_non_just' => $absNonJust,
        ];
    }

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, array<string, mixed>>
     */
    private function rapportRhLignesEmployes(Carbon $monthStart, Carbon $monthEnd, array $userIds): Collection
    {
        $penaltyUnit = max(0, (int) config('pointage.employe_penalty_retard_fcfa', 2500));

        $rows = collect();
        foreach ($userIds as $uid) {
            $u = User::query()->find($uid);
            if (! $u) {
                continue;
            }

            $mins = $this->minutesWorkedUserBetween((int) $uid, $monthStart, $monthEnd);
            $heures = (int) floor($mins / 60);

            $retards = (int) Pointage::query()
                ->where('user_id', $uid)
                ->whereBetween('clocked_at', [$monthStart, $monthEnd])
                ->where('type', 'arrivee')
                ->where('statut', 'retard')
                ->count();

            $absences = (int) PointageDeclaration::query()
                ->where('user_id', $uid)
                ->where('type', 'absence')
                ->where('statut', 'valide')
                ->whereBetween('date_concernee', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->count();

            $supH = (int) max(0, min(40, (int) floor($heures * 0.05)));
            $penalites = $retards * $penaltyUnit;

            $parts = preg_split('/\s+/u', trim((string) $u->name)) ?: [];
            $initials = count($parts) >= 2
                ? mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[count($parts) - 1], 0, 1))
                : mb_strtoupper(mb_substr((string) $u->name, 0, 2));

            $rows->push([
                'user_id' => (int) $uid,
                'nom' => (string) $u->name,
                'initials' => $initials,
                'heures_travaillees' => $heures.'h',
                'heures_travaillees_int' => $heures,
                'retards' => $retards,
                'absences' => $absences,
                'heures_sup' => $supH,
                'heures_sup_label' => $supH > 0 ? '+'.$supH.'h' : '—',
                'penalites_fcfa' => $penalites,
                'penalites_label' => $penalites > 0 ? '-'.number_format($penalites, 0, ',', ' ') : '—',
            ]);
        }

        return $rows;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function rapportRhSortRows(Collection $rows, string $onglet): Collection
    {
        return match ($onglet) {
            'retards' => $rows->sortByDesc('retards')->values(),
            'heures_sup' => $rows->sortByDesc('heures_sup')->values(),
            'paie' => $rows->sortByDesc('penalites_fcfa')->values(),
            default => $rows->sortBy('nom')->values(),
        };
    }

    private function countWeekdaysInRange(Carbon $from, Carbon $to): int
    {
        $n = 0;
        $d = $from->copy()->startOfDay();
        while ($d->lte($to)) {
            if (! $d->isWeekend()) {
                $n++;
            }
            $d->addDay();
        }

        return $n;
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
}

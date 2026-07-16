<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\PointageAuditLog;
use App\Models\PointageDeclaration;
use App\Models\Profil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PointageDeclarationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $user->profilCollaborateurAssocie();

        $query = PointageDeclaration::query()->with([
            'user:id,name,email',
            'managerUser:id,name',
            'rhUser:id,name',
        ]);

        $voirToutes = $request->boolean('toutes')
            && $user
            && ($user->isSuperAdmin() || $user->isAdmin() || $user->isRh());

        if (! $voirToutes && $user) {
            $query->where('user_id', $user->id);
        }

        $mois = $request->input('mois', Carbon::now()->format('Y-m'));
        if (! is_string($mois) || ! preg_match('/^\d{4}-\d{2}$/', $mois)) {
            $mois = Carbon::now()->format('Y-m');
        }
        [$year, $month] = array_map('intval', explode('-', $mois, 2));
        $query->whereYear('date_concernee', $year)->whereMonth('date_concernee', $month);

        $declarations = $query->orderByDesc('date_concernee')->orderByDesc('created_at')->paginate(20)->withQueryString();

        $declarations->setCollection(
            $declarations->getCollection()->map(fn (PointageDeclaration $d) => $this->serializeDeclarationRow($d))
        );

        $meta = $this->declarationFlowMetaForUser($user);

        $listQuery = ['mois' => $mois];
        if ($voirToutes) {
            $listQuery['toutes'] = '1';
        }

        return Inertia::render('pointage/DeclarationsIndex', [
            'declarations' => $declarations,
            'periode_mois' => $mois,
            'periode_label' => $this->frenchMonthYearLabel($mois),
            'validation_hint' => $meta['validation_hint'],
            'declarationListQuery' => $listQuery,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $meta = $this->declarationFlowMetaForUser($user);

        return Inertia::render('pointage/DeclarationsCreate', [
            'manager_nom' => $meta['manager_nom'],
            'validation_hint' => $meta['validation_hint'],
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'type' => 'required|in:retard,absence,conge,regularisation',
            'date_concernee' => 'required|date',
            'motif' => 'required|string|max:512',
            'commentaire' => 'nullable|string|max:2000',
            'justificatif' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $path = null;
        if ($request->hasFile('justificatif')) {
            $path = $request->file('justificatif')->store('pointage_declarations', 'local');
        }

        $user->profilCollaborateurAssocie();
        $profil = $user->profil;
        $statut = ($profil && $profil->n_plus_1_id) ? 'en_attente_manager' : 'en_attente_rh';

        PointageDeclaration::create([
            'user_id' => $user->id,
            'type' => $validated['type'],
            'date_concernee' => $validated['date_concernee'],
            'motif' => $validated['motif'],
            'commentaire' => $validated['commentaire'] ?? null,
            'justificatif_path' => $path,
            'statut' => $statut,
        ]);

        PointageAuditLog::record($user, 'DECLARATION_SOUMISE', 'Nouvelle déclaration pointage', null, $request->ip(), 'ok');

        return redirect()->route('pointage.declarations.index')
            ->with('success', 'Déclaration enregistrée.');
    }

    public function validationManager(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && ($user->isResponsableDepartement() || $user->isAdmin() || $user->isRh() || $user->isSuperAdmin()), 403);

        $pending = PointageDeclaration::query()
            ->with(['user:id,name,email'])
            ->where('statut', 'en_attente_manager')
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn (PointageDeclaration $d) => $this->userCanValidateAsManager($user, $d));

        $history = PointageDeclaration::query()
            ->with(['user:id,name,email', 'managerUser:id,name', 'rhUser:id,name'])
            ->whereIn('statut', ['en_attente_rh', 'valide', 'rejete'])
            ->orderByDesc('updated_at')
            ->limit(80)
            ->get();

        return Inertia::render('pointage/ValidationsManager', [
            'pending' => $pending->values(),
            'history' => $history,
        ]);
    }

    public function decisionManager(Request $request, PointageDeclaration $declaration)
    {
        $user = Auth::user();
        abort_unless($user && $this->userCanValidateAsManager($user, $declaration), 403);
        abort_unless($declaration->statut === 'en_attente_manager', 422);

        $validated = $request->validate([
            'accept' => 'required|boolean',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validated['accept']) {
            $declaration->update([
                'statut' => 'en_attente_rh',
                'manager_user_id' => $user->id,
                'manager_decided_at' => now(),
                'manager_comment' => $validated['comment'] ?? null,
            ]);
            PointageAuditLog::record($user, 'DECLARATION_VAL_MANAGER_OK', 'Transmis RH', null, $request->ip(), 'ok', ['declaration_id' => $declaration->id]);
        } else {
            $declaration->update([
                'statut' => 'rejete',
                'manager_user_id' => $user->id,
                'manager_decided_at' => now(),
                'manager_comment' => $validated['comment'] ?? null,
            ]);
            PointageAuditLog::record($user, 'DECLARATION_VAL_MANAGER_KO', 'Rejet manager', null, $request->ip(), 'alerte', ['declaration_id' => $declaration->id]);
        }

        return back()->with('success', 'Décision enregistrée.');
    }

    public function validationRh(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && ($user->isRh() || $user->isAdmin() || $user->isSuperAdmin()), 403);

        return redirect()->route('pointage.rh.presence.recuperation-pointages');
    }

    public function decisionRh(Request $request, PointageDeclaration $declaration)
    {
        $user = Auth::user();
        abort_unless($user && ($user->isRh() || $user->isAdmin() || $user->isSuperAdmin()), 403);
        abort_unless($declaration->statut === 'en_attente_rh', 422);

        $validated = $request->validate([
            'accept' => 'required|boolean',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validated['accept']) {
            $declaration->update([
                'statut' => 'valide',
                'rh_user_id' => $user->id,
                'rh_decided_at' => now(),
                'rh_comment' => $validated['comment'] ?? null,
            ]);
            PointageAuditLog::record($user, 'DECLARATION_VAL_RH_OK', 'Déclaration validée RH', null, $request->ip(), 'ok', ['declaration_id' => $declaration->id]);
        } else {
            $declaration->update([
                'statut' => 'rejete',
                'rh_user_id' => $user->id,
                'rh_decided_at' => now(),
                'rh_comment' => $validated['comment'] ?? null,
            ]);
            PointageAuditLog::record($user, 'DECLARATION_VAL_RH_KO', 'Déclaration rejetée RH', null, $request->ip(), 'alerte', ['declaration_id' => $declaration->id]);
        }

        return back()->with('success', 'Décision RH enregistrée.');
    }

    private function userCanValidateAsManager(\App\Models\User $user, PointageDeclaration $declaration): bool
    {
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        $declaration->user->profilCollaborateurAssocie();
        $declarerProfil = $declaration->user->profil;

        $user->profilCollaborateurAssocie();
        $me = $user->profil;

        if (! $declarerProfil || ! $me) {
            return false;
        }

        if ($declarerProfil->n_plus_1_id === $me->id) {
            return true;
        }

        if (! $user->isResponsableDepartement()) {
            return false;
        }

        $managed = Departement::query()
            ->where('responsable_departement_id', $me->id)
            ->where('actif', true)
            ->pluck('nom')
            ->map(fn ($n) => mb_strtolower(trim((string) $n)));

        $dept = mb_strtolower(trim((string) ($declarerProfil->getRawOriginal('departement') ?? $declarerProfil->departement)));

        return $managed->contains($dept);
    }

    private function serializeDeclarationRow(PointageDeclaration $d): array
    {
        $path = $d->justificatif_path;
        $fileLabel = $path ? basename((string) $path) : null;

        return [
            'id' => $d->id,
            'type' => $d->type,
            'type_label' => $this->typeLabel($d->type),
            'date_concernee' => $d->date_concernee?->format('Y-m-d'),
            'date_concernee_display' => $d->date_concernee?->format('d/m/Y'),
            'motif' => $d->motif,
            'commentaire' => $d->commentaire,
            'has_justificatif' => (bool) $path,
            'justificatif_filename' => $fileLabel,
            'statut' => $d->statut,
            'statut_label' => $this->statutLabel($d->statut),
            'validateur_label' => $this->validateurLabel($d),
        ];
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'retard' => 'Retard',
            'absence' => 'Absence',
            'conge' => 'Congé',
            'regularisation' => 'Régularisation',
            default => $type,
        };
    }

    private function statutLabel(string $statut): string
    {
        return match ($statut) {
            'en_attente_manager', 'en_attente_rh' => 'En attente',
            'valide' => 'Validé',
            'rejete' => 'Rejeté',
            default => $statut,
        };
    }

    private function validateurLabel(PointageDeclaration $d): string
    {
        if (in_array($d->statut, ['en_attente_manager', 'en_attente_rh'], true)) {
            return '—';
        }

        if ($d->statut === 'rejete') {
            if ($d->rh_decided_at && $d->rhUser) {
                return $d->rhUser->name.' (RH)';
            }
            if ($d->managerUser) {
                return $d->managerUser->name.' (Manager)';
            }

            return '—';
        }

        if ($d->statut === 'valide' && $d->rhUser) {
            return $d->rhUser->name.' (RH)';
        }

        return '—';
    }

    private function frenchMonthYearLabel(string $ym): string
    {
        if (! preg_match('/^(\d{4})-(\d{2})$/', $ym, $m)) {
            return $ym;
        }
        $monthNum = (int) $m[2];
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        return ($months[$monthNum] ?? $ym).' '.$m[1];
    }

    /**
     * @return array{manager_nom: string|null, validation_hint: string}
     */
    private function declarationFlowMetaForUser(?\App\Models\User $user): array
    {
        $user?->profilCollaborateurAssocie();
        $profil = $user?->profil;
        if (! $profil?->n_plus_1_id) {
            return [
                'manager_nom' => null,
                'validation_hint' => 'Votre déclaration sera soumise directement à la RH pour validation.',
            ];
        }

        $mgr = Profil::query()->find($profil->n_plus_1_id);
        $nom = $mgr ? trim(($mgr->prenom ?? '').' '.($mgr->nom ?? '')) : null;
        if ($nom === '') {
            $nom = null;
        }

        $hint = $nom
            ? "Votre déclaration sera soumise à {$nom} (Manager) pour validation, puis à la RH."
            : 'Votre déclaration sera soumise à votre manager pour validation, puis à la RH.';

        return [
            'manager_nom' => $nom,
            'validation_hint' => $hint,
        ];
    }
}

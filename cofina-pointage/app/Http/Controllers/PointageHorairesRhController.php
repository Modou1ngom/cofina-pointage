<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\PointageFerieImportPref;
use App\Models\PointageHoraireJourSemaine;
use App\Models\PointageHoraireProfile;
use App\Models\PointageJourFerie;
use App\Models\PointagePausesRegle;
use App\Models\Profil;
use App\Services\NagerPublicHolidaysService;
use App\Services\Pointage\PointageHorairesCalendrierService;
use App\Services\Pointage\PointageJourFerieAutoPointageService;
use App\Services\Pointage\PointageJourFerieImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PointageHorairesRhController extends Controller
{
    public function __construct(
        private readonly PointageHorairesCalendrierService $calendrier
    ) {}

    public function joursOuvrables(Request $request): Response|RedirectResponse
    {
        if ($request->isMethod('post')) {
            return $this->saveJoursOuvrables($request);
        }

        $profile = $this->resolveProfile($request);

        return Inertia::render('pointage/Presence/JoursOuvrables', $this->sharedProps($profile));
    }

    public function weekEnds(Request $request): Response|RedirectResponse
    {
        if ($request->isMethod('post')) {
            return $this->saveWeekEnds($request);
        }

        $profile = $this->resolveProfile($request);

        return Inertia::render('pointage/Presence/WeekEnds', $this->sharedProps($profile));
    }

    public function joursFeries(Request $request): Response|RedirectResponse
    {
        if ($request->isMethod('post')) {
            return $this->storeJourFerie($request);
        }

        $country = $request->query('country_code', 'SN');
        $departementId = $request->query('departement_id');
        $departementId = $departementId !== null && $departementId !== '' ? (int) $departementId : null;

        $feries = $this->calendrier
            ->queryFeriesFiltered($country === 'all' ? null : (string) $country, $departementId)
            ->take(500)
            ->values();

        $importPref = null;
        if (Schema::hasTable('pointage_ferie_import_prefs') && $country !== 'all') {
            $importPref = PointageFerieImportPref::query()
                ->where('country_code', strtoupper((string) $country))
                ->first();
        }

        return Inertia::render('pointage/Presence/JoursFeries', [
            'feries' => $feries,
            'types' => $this->ferieTypesOptions(),
            'pays_disponibles' => config('pointage.nager_pays_disponibles', []),
            'departements' => Departement::query()->where('actif', true)->orderBy('nom')->get(['id', 'nom']),
            'filters' => [
                'country_code' => $country,
                'departement_id' => $departementId,
                'import_year' => (int) $request->query('import_year', (string) now()->year),
            ],
            'import_pref' => $importPref ? [
                'country_code' => $importPref->country_code,
                'auto_importer_annuel' => $importPref->auto_importer_annuel,
            ] : null,
        ]);
    }

    public function previewJoursFeriesNager(Request $request, NagerPublicHolidaysService $nager): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'country_code' => ['required', 'string', 'size:2'],
        ]);

        $year = (int) $validated['year'];
        $countryCode = strtoupper($validated['country_code']);
        $res = $nager->fetchSafe($year, $countryCode);

        if (! $res['ok']) {
            return response()->json(['ok' => false, 'message' => $res['error']], 422);
        }

        $items = [];
        foreach ($res['items'] as $it) {
            $date = $it['date'];
            $exists = PointageJourFerie::query()
                ->where('source', 'official')
                ->where('country_code', $countryCode)
                ->whereDate('date_unique', $date)
                ->exists();

            $items[] = [
                'date' => $date,
                'libelle' => $it['localName'] !== '' ? $it['localName'] : $it['name'],
                'country_code' => $countryCode,
                'already_imported' => $exists,
                'selected' => ! $exists,
            ];
        }

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function confirmJoursFeriesNager(
        Request $request,
        PointageJourFerieImportService $importer,
        PointageJourFerieAutoPointageService $autoPointage,
    ): RedirectResponse {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'country_code' => ['required', 'string', 'size:2'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.date' => ['required', 'date'],
            'items.*.libelle' => ['required', 'string', 'max:191'],
            'auto_importer_annuel' => ['boolean'],
        ]);

        $year = (int) $validated['year'];
        $countryCode = strtoupper($validated['country_code']);
        $holidays = array_map(fn (array $row) => [
            'date' => substr((string) $row['date'], 0, 10),
            'libelle' => $row['libelle'],
        ], $validated['items']);

        $stats = $importer->importOfficialHolidays($year, $countryCode, $holidays);

        if (Schema::hasTable('pointage_ferie_import_prefs')) {
            PointageFerieImportPref::query()->updateOrCreate(
                ['country_code' => $countryCode],
                ['auto_importer_annuel' => $request->boolean('auto_importer_annuel')]
            );
        }

        $autoCreated = 0;
        foreach ($holidays as $row) {
            $date = $row['date'] ?? null;
            if (! is_string($date)) {
                continue;
            }
            $f = PointageJourFerie::query()
                ->where('source', 'official')
                ->where('country_code', $countryCode)
                ->whereDate('date_unique', substr($date, 0, 10))
                ->first();
            if ($f === null || $f->travaille_avec_majoration) {
                continue;
            }
            $res = $autoPointage->generateForFerie($f, includePastDates: false);
            $autoCreated += $res['created_pointages'];
        }

        $msg = "Import terminé : {$stats['created']} créé(s), {$stats['skipped']} ignoré(s) (doublons).";
        if ($autoCreated > 0) {
            $msg .= " {$autoCreated} pointage(s) auto créés pour le staff.";
        }

        return back()->with('success', $msg);
    }

    public function updateJourFerie(
        Request $request,
        PointageJourFerie $ferie,
        PointageJourFerieAutoPointageService $autoPointage,
    ): RedirectResponse {
        $data = $this->validateJourFeriePayload($request);
        $etaitChome = ! (bool) $ferie->travaille_avec_majoration;
        $ferie->update($data);

        $message = 'Jour férié mis à jour.';
        if (! (bool) $ferie->travaille_avec_majoration) {
            $stats = $autoPointage->generateForFerie($ferie->refresh());
            if ($stats['created_pointages'] > 0) {
                $message .= " {$stats['created_pointages']} pointage(s) auto créés pour le staff.";
            }
        } elseif ($etaitChome) {
            $message .= ' Le férié est désormais travaillé avec majoration : les pointages auto précédents restent en base, à supprimer manuellement si nécessaire.';
        }

        return back()->with('success', $message);
    }

    public function destroyJourFerie(PointageJourFerie $ferie): RedirectResponse
    {
        $ferie->delete();

        return back()->with('success', 'Jour férié supprimé.');
    }

    public function cloneJourFerie(Request $request, PointageJourFerie $ferie): RedirectResponse
    {
        $validated = $request->validate([
            'departement_ids' => ['required', 'array', 'min:1'],
            'departement_ids.*' => ['integer', 'exists:departements,id'],
        ]);

        $created = 0;
        $skipped = 0;

        foreach ($validated['departement_ids'] as $depId) {
            $depId = (int) $depId;
            if ((int) $ferie->departement_id === $depId) {
                $skipped++;

                continue;
            }

            $exists = PointageJourFerie::query()
                ->where('departement_id', $depId)
                ->where('libelle', $ferie->libelle)
                ->whereDate('date_unique', $ferie->date_unique)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            $clone = $ferie->replicate();
            $clone->departement_id = $depId;
            $clone->source = 'manual';
            $clone->save();
            $created++;
        }

        return back()->with('success', "Clonage : {$created} créé(s), {$skipped} ignoré(s).");
    }

    public function joursFeriesCalendrier(Request $request): Response
    {
        $profile = $this->resolveProfile($request);
        $year = max(2000, min(2100, (int) $request->query('year', (string) now()->year)));
        $month = max(1, min(12, (int) $request->query('month', (string) now()->month)));
        $view = $request->query('view', 'month') === 'year' ? 'year' : 'month';

        $country = $request->query('country_code', 'all');
        $departementId = $request->query('departement_id');
        $departementId = $departementId !== null && $departementId !== '' ? (int) $departementId : null;

        $feries = $this->calendrier->queryFeriesFiltered(
            $country === 'all' ? null : (string) $country,
            $departementId
        );

        $grille = $view === 'year'
            ? $this->calendrier->grilleAnnuelle($year, $profile, $feries)
            : $this->calendrier->grilleMensuelle($year, $month, $profile, $feries);

        $feriesList = $feries->map(fn (PointageJourFerie $f) => [
            'id' => $f->id,
            'libelle' => $f->libelle,
            'date_unique' => $f->date_unique?->format('Y-m-d'),
            'date_fin' => $f->date_fin?->format('Y-m-d'),
            'type' => $f->type,
            'source' => $f->source,
            'country_code' => $f->country_code,
            'pays_region' => $f->pays_region,
            'travaille_avec_majoration' => $f->travaille_avec_majoration,
            'taux_majoration_pct' => (float) $f->taux_majoration_pct,
            'recurrence_annuelle' => $f->recurrence_annuelle,
            'notes' => $f->notes,
        ])->values();

        return Inertia::render('pointage/Presence/JoursFeriesCalendrier', array_merge($this->sharedProps($profile), [
            'year' => $year,
            'month' => $month,
            'view' => $view,
            'grille' => $grille,
            'feries_list' => $feriesList,
            'pays_disponibles' => config('pointage.nager_pays_disponibles', []),
            'filters' => [
                'country_code' => $country,
                'departement_id' => $departementId,
            ],
        ]));
    }

    public function joursFeriesCalendrierPdf(Request $request): HttpResponse
    {
        $profile = $this->resolveProfile($request);
        $year = max(2000, min(2100, (int) $request->query('year', (string) now()->year)));
        $country = $request->query('country_code', 'all');
        $departementId = $request->query('departement_id');
        $departementId = $departementId !== null && $departementId !== '' ? (int) $departementId : null;

        $feries = $this->calendrier->queryFeriesFiltered(
            $country === 'all' ? null : (string) $country,
            $departementId
        );
        $grilleAnnuelle = $this->calendrier->grilleAnnuelle($year, $profile, $feries);

        $monthNames = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
            7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        $html = view('pdf.pointage-calendrier-feries', [
            'year' => $year,
            'profile' => $profile,
            'grilleAnnuelle' => $grilleAnnuelle,
            'monthNames' => $monthNames,
            'country' => $country,
        ])->render();

        $dompdf = new \Dompdf\Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="calendrier-feries-'.$year.'.pdf"',
        ]);
    }

    public function pauseDejeuner(Request $request): Response|RedirectResponse
    {
        return $this->pausesPage($request, 'dejeuner');
    }

    public function pauseTechnique(Request $request): Response|RedirectResponse
    {
        return $this->pausesPage($request, 'technique');
    }

    public function pauseDuree(Request $request): Response|RedirectResponse
    {
        return $this->pausesPage($request, 'duree');
    }

    private function pausesPage(Request $request, string $tab): Response|RedirectResponse
    {
        if ($request->isMethod('post')) {
            return $this->savePauses($request);
        }

        $profile = $this->resolveProfile($request);

        return Inertia::render('pointage/Presence/PausesConfig', array_merge($this->sharedProps($profile), [
            'pauseTab' => $tab,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function sharedProps(PointageHoraireProfile $profile): array
    {
        $profile->load(['joursSemaine', 'pausesRegle', 'departement', 'profilCollaborateur']);

        $profiles = PointageHoraireProfile::query()
            ->with(['departement:id,nom', 'profilCollaborateur:id,nom,prenom'])
            ->orderBy('libelle')
            ->get();

        return [
            'profiles' => $profiles,
            'selected_profile_id' => $profile->id,
            'profile' => $profile,
            'departements' => Departement::query()->where('actif', true)->orderBy('nom')->get(['id', 'nom']),
            'profils' => Profil::query()->orderBy('nom')->orderBy('prenom')->limit(800)->get(['id', 'nom', 'prenom', 'matricule', 'departement']),
        ];
    }

    private function resolveProfile(Request $request): PointageHoraireProfile
    {
        $id = (int) $request->query('profile_id', $request->input('profile_id', 0));
        if ($id > 0) {
            return PointageHoraireProfile::query()
                ->with(['joursSemaine', 'pausesRegle'])
                ->findOrFail($id);
        }

        $p = PointageHoraireProfile::query()
            ->where('scope_type', 'global')
            ->where('actif', true)
            ->with(['joursSemaine', 'pausesRegle'])
            ->orderBy('id')
            ->first();

        if ($p !== null) {
            return $p;
        }

        return PointageHoraireProfile::query()->with(['joursSemaine', 'pausesRegle'])->orderBy('id')->firstOrFail();
    }

    private function saveJoursOuvrables(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'profile_id' => ['required', 'integer', 'exists:pointage_horaire_profiles,id'],
            'jours' => ['required', 'array', 'size:7'],
            'jours.*.day_of_week' => ['required', 'integer', Rule::in([0, 1, 2, 3, 4, 5, 6])],
            'jours.*.est_ouvrable' => ['required', 'boolean'],
            'jours.*.heure_debut' => ['nullable', 'date_format:H:i'],
            'jours.*.heure_fin' => ['nullable', 'date_format:H:i'],
            'jours.*.duree_theorique_heures' => ['nullable', 'numeric', 'between:0,24'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['jours'] as $row) {
                PointageHoraireJourSemaine::query()->updateOrCreate(
                    [
                        'horaire_profile_id' => $validated['profile_id'],
                        'day_of_week' => $row['day_of_week'],
                    ],
                    [
                        'est_ouvrable' => $row['est_ouvrable'],
                        'heure_debut' => $row['heure_debut'] ?? null,
                        'heure_fin' => $row['heure_fin'] ?? null,
                        'duree_theorique_heures' => $row['duree_theorique_heures'] ?? null,
                    ]
                );
            }
        });

        return back()->with('success', 'Jours ouvrables enregistrés.');
    }

    private function saveWeekEnds(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'profile_id' => ['required', 'integer', 'exists:pointage_horaire_profiles,id'],
            'weekend_jours' => ['required', 'array', 'min:1'],
            'weekend_jours.*' => ['integer', Rule::in([0, 1, 2, 3, 4, 5, 6])],
            'weekend_samedi_matin_ouvrable' => ['boolean'],
            'weekend_samedi_matin_fin' => ['nullable', 'date_format:H:i'],
            'weekend_dimanche_matin_ouvrable' => ['boolean'],
            'weekend_dimanche_matin_fin' => ['nullable', 'date_format:H:i'],
            'weekend_travail_majoration_pct' => ['nullable', 'numeric', 'between:0,500'],
        ]);

        $p = PointageHoraireProfile::query()->findOrFail($validated['profile_id']);
        $p->update([
            'weekend_jours' => array_values(array_unique(array_map('intval', $validated['weekend_jours']))),
            'weekend_samedi_matin_ouvrable' => $validated['weekend_samedi_matin_ouvrable'] ?? false,
            'weekend_samedi_matin_fin' => $validated['weekend_samedi_matin_fin'] ?? null,
            'weekend_dimanche_matin_ouvrable' => $validated['weekend_dimanche_matin_ouvrable'] ?? false,
            'weekend_dimanche_matin_fin' => $validated['weekend_dimanche_matin_fin'] ?? null,
            'weekend_travail_majoration_pct' => $validated['weekend_travail_majoration_pct'] ?? 25,
        ]);

        return back()->with('success', 'Paramètres week-end enregistrés.');
    }

    private function savePauses(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'profile_id' => ['required', 'integer', 'exists:pointage_horaire_profiles,id'],
            'dejeuner_duree_minutes' => ['required', 'integer', 'between:0,180'],
            'dejeuner_fenetre_debut' => ['required', 'date_format:H:i'],
            'dejeuner_fenetre_fin' => ['required', 'date_format:H:i'],
            'dejeuner_mode' => ['required', Rule::in(['auto_deduct', 'pointage_reel'])],
            'technique_nb_max' => ['required', 'integer', 'between:0,20'],
            'technique_duree_max_minutes' => ['required', 'integer', 'between:0,120'],
            'technique_decompte_temps_travail' => ['required', 'boolean'],
            'pause_totale_max_minutes' => ['nullable', 'integer', 'between:0,600'],
            'alerte_depassement_pause' => ['required', 'boolean'],
        ]);

        PointagePausesRegle::query()->updateOrCreate(
            ['horaire_profile_id' => $validated['profile_id']],
            [
                'dejeuner_duree_minutes' => $validated['dejeuner_duree_minutes'],
                'dejeuner_fenetre_debut' => $validated['dejeuner_fenetre_debut'],
                'dejeuner_fenetre_fin' => $validated['dejeuner_fenetre_fin'],
                'dejeuner_mode' => $validated['dejeuner_mode'],
                'technique_nb_max' => $validated['technique_nb_max'],
                'technique_duree_max_minutes' => $validated['technique_duree_max_minutes'],
                'technique_decompte_temps_travail' => $validated['technique_decompte_temps_travail'],
                'pause_totale_max_minutes' => $validated['pause_totale_max_minutes'] ?? null,
                'alerte_depassement_pause' => $validated['alerte_depassement_pause'],
            ]
        );

        return back()->with('success', 'Règles de pauses enregistrées.');
    }

    private function storeJourFerie(Request $request): RedirectResponse
    {
        $data = $this->validateJourFeriePayload($request);
        $dateStr = is_string($data['date_unique']) ? $data['date_unique'] : $request->input('date_unique');
        $ferie = PointageJourFerie::query()->create($data + [
            'source' => 'manual',
            'annee' => (int) substr((string) $dateStr, 0, 4),
        ]);

        $message = 'Jour férié ajouté.';
        if (! (bool) $ferie->travaille_avec_majoration) {
            $stats = app(PointageJourFerieAutoPointageService::class)
                ->generateForFerie($ferie);
            if ($stats['created_pointages'] > 0) {
                $message .= " {$stats['created_pointages']} pointage(s) auto créés pour le staff sur ce jour férié chômé.";
            }
        }

        return back()->with('success', $message);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function ferieTypesOptions(): array
    {
        return [
            ['value' => 'national', 'label' => 'Férié national'],
            ['value' => 'local', 'label' => 'Férié local'],
            ['value' => 'religious', 'label' => 'Férié religieux'],
            ['value' => 'company', 'label' => 'Événement entreprise'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateJourFeriePayload(Request $request): array
    {
        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:191'],
            'date_unique' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_unique'],
            'recurrence_annuelle' => ['boolean'],
            'pays_region' => ['nullable', 'string', 'max:191'],
            'departement_id' => ['nullable', 'integer', 'exists:departements,id'],
            'country_code' => ['nullable', 'string', 'max:3'],
            'type' => ['required', Rule::in(['national', 'local', 'religious', 'company'])],
            'travaille_avec_majoration' => ['boolean'],
            'taux_majoration_pct' => ['nullable', 'numeric', 'between:0,500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['recurrence_annuelle'] = $request->boolean('recurrence_annuelle');
        $data['travaille_avec_majoration'] = $request->boolean('travaille_avec_majoration');
        $data['taux_majoration_pct'] = $data['taux_majoration_pct'] ?? 0;
        if ($data['country_code'] !== null && $data['country_code'] !== '') {
            $data['country_code'] = strtoupper($data['country_code']);
        }
        if (empty($data['departement_id'])) {
            $data['departement_id'] = null;
        }

        return $data;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PointageAffectation extends Model
{
    protected $fillable = [
        'profil_id',
        'user_id',
        'type_pointage',
        'mode_validation',
        'date_affectation',
        'date_fin_affectation',
        'statut_activation',
        'enrolled_by',
        'enrolled_at',
    ];

    protected function casts(): array
    {
        return [
            'date_affectation' => 'date',
            'date_fin_affectation' => 'date',
            'statut_activation' => 'boolean',
            'enrolled_at' => 'datetime',
        ];
    }

    public function profil(): BelongsTo
    {
        return $this->belongsTo(Profil::class, 'profil_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrolledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    public function agences(): BelongsToMany
    {
        return $this->belongsToMany(Agence::class, 'pointage_affectation_agences', 'pointage_affectation_id', 'agence_id')
            ->withPivot([
                'date_debut_autorisation',
                'date_fin_autorisation',
                'statut_agence',
                'niveau_acces',
                'is_default',
            ])
            ->withTimestamps();
    }

    public function syncUserLinkFromProfilEmail(): void
    {
        $this->loadMissing('profil');
        $email = $this->profil?->email;
        if ($email === null || trim($email) === '') {
            return;
        }
        $user = User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim($email))])
            ->first();
        if ($user !== null && $this->user_id !== $user->id) {
            $this->user_id = $user->id;
            $this->saveQuietly();
        }
    }

    public function syncLegacyUserSettings(): void
    {
        if ($this->user_id === null) {
            return;
        }
        PointageUserAffectationSetting::query()->updateOrCreate(
            ['user_id' => $this->user_id],
            [
                'type_pointage' => $this->type_pointage,
                'mode_validation' => $this->mode_validation,
                'date_affectation' => $this->date_affectation,
                'date_fin_affectation' => $this->date_fin_affectation,
                'statut_activation' => $this->statut_activation,
            ]
        );
    }

    /**
     * Aligne agence_user avec les agences autorisées de l'affectation pointage.
     */
    public function syncAgencesToUserPivot(): void
    {
        $this->syncUserLinkFromProfilEmail();

        if ($this->user_id === null) {
            return;
        }

        $user = User::query()->find($this->user_id);
        if ($user === null) {
            return;
        }

        $this->loadMissing('agences');

        $syncData = [];
        foreach ($this->agences as $agence) {
            $syncData[$agence->id] = [
                'is_default' => (bool) ($agence->pivot->is_default ?? false),
                'date_debut_autorisation' => $agence->pivot->date_debut_autorisation,
                'date_fin_autorisation' => $agence->pivot->date_fin_autorisation,
                'statut_agence' => $agence->pivot->statut_agence ?? 'actif',
                'niveau_acces' => $agence->pivot->niveau_acces ?? 'pointage_complet',
            ];
        }

        $user->agences()->sync($syncData);
    }
}

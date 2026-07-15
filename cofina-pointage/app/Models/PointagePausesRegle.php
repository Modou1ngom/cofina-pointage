<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointagePausesRegle extends Model
{
    protected $table = 'pointage_pauses_regle';

    protected $fillable = [
        'horaire_profile_id',
        'dejeuner_duree_minutes',
        'dejeuner_fenetre_debut',
        'dejeuner_fenetre_fin',
        'dejeuner_mode',
        'technique_nb_max',
        'technique_duree_max_minutes',
        'technique_decompte_temps_travail',
        'pause_totale_max_minutes',
        'alerte_depassement_pause',
    ];

    protected function casts(): array
    {
        return [
            'technique_decompte_temps_travail' => 'boolean',
            'alerte_depassement_pause' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PointageHoraireProfile::class, 'horaire_profile_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointageCalendrierPause extends Model
{
    protected $table = 'pointage_calendrier_pauses';

    protected $fillable = [
        'calendrier_profil_id',
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

    public function profilCalendrier(): BelongsTo
    {
        return $this->belongsTo(PointageCalendrierProfil::class, 'calendrier_profil_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointageHoraireJourSemaine extends Model
{
    protected $table = 'pointage_horaire_jours_semaine';

    protected $fillable = [
        'horaire_profile_id',
        'day_of_week',
        'est_ouvrable',
        'heure_debut',
        'heure_fin',
        'duree_theorique_heures',
    ];

    protected function casts(): array
    {
        return [
            'est_ouvrable' => 'boolean',
            'duree_theorique_heures' => 'decimal:2',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PointageHoraireProfile::class, 'horaire_profile_id');
    }
}

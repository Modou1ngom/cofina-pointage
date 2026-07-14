<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointageCalendrierJourSemaine extends Model
{
    protected $table = 'pointage_calendrier_jours_semaine';

    protected $fillable = [
        'calendrier_profil_id',
        'jour_semaine',
        'ouvrable',
        'heure_debut',
        'heure_fin',
        'duree_theorique_heures',
    ];

    protected function casts(): array
    {
        return [
            'ouvrable' => 'boolean',
            'duree_theorique_heures' => 'decimal:2',
        ];
    }

    public function profil(): BelongsTo
    {
        return $this->belongsTo(PointageCalendrierProfil::class, 'calendrier_profil_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointageCalendrierWeekend extends Model
{
    protected $table = 'pointage_calendrier_weekends';

    protected $fillable = [
        'calendrier_profil_id',
        'jours',
        'partiel_actif',
        'partiel_jour',
        'partiel_debut',
        'partiel_fin',
        'heures_sup_weekend_pct',
    ];

    protected function casts(): array
    {
        return [
            'jours' => 'array',
            'partiel_actif' => 'boolean',
            'heures_sup_weekend_pct' => 'decimal:2',
        ];
    }

    public function profilCalendrier(): BelongsTo
    {
        return $this->belongsTo(PointageCalendrierProfil::class, 'calendrier_profil_id');
    }
}

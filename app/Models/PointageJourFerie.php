<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointageJourFerie extends Model
{
    protected $table = 'pointage_jours_feries';

    protected $fillable = [
        'libelle',
        'date_unique',
        'date_fin',
        'recurrence_annuelle',
        'pays_region',
        'departement_id',
        'country_code',
        'type',
        'travaille_avec_majoration',
        'taux_majoration_pct',
        'source',
        'annee',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_unique' => 'date',
            'date_fin' => 'date',
            'recurrence_annuelle' => 'boolean',
            'travaille_avec_majoration' => 'boolean',
            'taux_majoration_pct' => 'decimal:2',
        ];
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'departement_id');
    }
}

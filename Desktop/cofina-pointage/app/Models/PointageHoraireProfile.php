<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PointageHoraireProfile extends Model
{
    protected $table = 'pointage_horaire_profiles';

    protected $fillable = [
        'libelle',
        'scope_type',
        'departement_id',
        'profile_id',
        'actif',
        'weekend_jours',
        'weekend_samedi_matin_ouvrable',
        'weekend_samedi_matin_fin',
        'weekend_dimanche_matin_ouvrable',
        'weekend_dimanche_matin_fin',
        'weekend_travail_majoration_pct',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'weekend_jours' => 'array',
            'weekend_samedi_matin_ouvrable' => 'boolean',
            'weekend_dimanche_matin_ouvrable' => 'boolean',
            'weekend_travail_majoration_pct' => 'decimal:2',
        ];
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function profilCollaborateur(): BelongsTo
    {
        return $this->belongsTo(Profil::class, 'profile_id');
    }

    public function joursSemaine(): HasMany
    {
        return $this->hasMany(PointageHoraireJourSemaine::class, 'horaire_profile_id');
    }

    public function pausesRegle(): HasOne
    {
        return $this->hasOne(PointagePausesRegle::class, 'horaire_profile_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointageUserAffectationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'type_pointage',
        'mode_validation',
        'date_affectation',
        'date_fin_affectation',
        'statut_activation',
    ];

    protected function casts(): array
    {
        return [
            'date_affectation' => 'date',
            'date_fin_affectation' => 'date',
            'statut_activation' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

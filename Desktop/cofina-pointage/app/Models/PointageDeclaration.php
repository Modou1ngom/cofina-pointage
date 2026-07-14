<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointageDeclaration extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'date_concernee',
        'motif',
        'commentaire',
        'justificatif_path',
        'statut',
        'manager_user_id',
        'manager_decided_at',
        'manager_comment',
        'rh_user_id',
        'rh_decided_at',
        'rh_comment',
    ];

    protected function casts(): array
    {
        return [
            'date_concernee' => 'date',
            'manager_decided_at' => 'datetime',
            'rh_decided_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function managerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function rhUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rh_user_id');
    }
}

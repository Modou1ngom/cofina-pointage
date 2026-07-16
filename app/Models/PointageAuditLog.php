<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointageAuditLog extends Model
{
    public static function record(
        ?User $actor,
        string $action,
        ?string $description = null,
        ?Agence $agence = null,
        ?string $ip = null,
        string $severity = 'ok',
        ?array $meta = null
    ): void {
        self::query()->create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'description' => $description,
            'agence_id' => $agence?->id,
            'ip_address' => $ip,
            'severity' => $severity,
            'meta' => $meta,
        ]);
    }

    protected $fillable = [
        'actor_user_id',
        'action',
        'description',
        'agence_id',
        'ip_address',
        'severity',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class, 'agence_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointrustDevice extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'model',
        'os_version',
        'app_version',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

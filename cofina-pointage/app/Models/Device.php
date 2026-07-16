<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'serial_number',
        'model',
        'os_version',
        'app_version',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function displaySerial(): string
    {
        $serial = trim((string) ($this->serial_number ?? ''));

        return $serial !== '' ? $serial : trim((string) ($this->device_id ?? ''));
    }
}
